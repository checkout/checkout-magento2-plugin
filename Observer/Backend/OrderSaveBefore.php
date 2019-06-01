<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Payment\Transaction;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;


class OrderSaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    protected $backendAuthSession;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Array
     */
    protected $params;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->vaultHandler = $vaultHandler;
        $this->config = $config;
        $this->utilities = $utilities;

        // Get the request parameters
        $this->params = $this->request->getParams();
    }
 
    /**
     * OrderSaveBefore constructor.
     */
    public function execute(Observer $observer)
    {
        try {
            // Get the order
            $this->order = $observer->getEvent()->getOrder();

            // Get the method id
            $this->methodId = $this->order->getPayment()->getMethodInstance()->getCode();

            // Process the payment
            if ($this->needsMotoProcessing()) {
                // Set the source
                $source = $this->getSource();

                // Set the payment
                $request = new Payment(
                    $source,
                    $this->order->getOrderCurrencyCode()
                );

                // Prepare the metadata array
                $request->metadata = ['methodId' => $this->methodId];

                // Prepare the capture date setting
                $captureDate = $this->config->getCaptureTime($this->methodId);
                
                // Set the request parameters
                $request->capture = $this->config->needsAutoCapture($this->methodId);
                $request->amount = $this->order->getGrandTotal()*100;
                $request->reference = $this->order->getIncrementId();
                $request->payment_ip = $this->remoteAddress->getRemoteAddress();
                // Todo - add customer to the request
                //$request->customer = $this->apiHandler->createCustomer($this->order);
                if ($captureDate) {
                    $request->capture_time = $this->config->getCaptureTime();
                }

                // Send the charge request
                $response = $this->apiHandler->checkoutApi
                    ->payments()
                    ->request($request);

                // Add the response to the order
                if ($this->apiHandler->isValidResponse($response)) {
                    $this->utilities->setPaymentData($this->order, $response);
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The transaction could not be processed.')
                    );
                }
            }
        }
        catch (\Exception $e) {

        }

        return $this;
    }

    /**
     * Checks if the MOTO logic should be triggered.
     */
    protected function needsMotoProcessing() {
        return $this->backendAuthSession->isLoggedIn()
        && isset($this->params['ckoCardToken'])
        && $this->methodId == 'checkoutcom_moto'
        && !$this->orderHandler->hasTransaction($this->order, Transaction::TYPE_AUTH);
    }

    /**
     * Provide a source from request.
     */
    protected function getSource() {
        if ($this->isCardToken()) {
            return new TokenSource($this->params['ckoCardToken']);
        }
        else if ($this->isSavedCard()) {
            $card = $this->vaultHandler->getCardFromHash(
                $this->params['publicHash'],
                $this->order->getCustomerId()
            );
            $idSource = new IdSource($card->getGatewayToken());
            $idSource->cvv = $this->params['cvv'];

            return $idSource;
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __('Please provide the required card information for payment.')
        );
    }

    /**
     * Checks if a card token is available.
     */
    protected function isCardToken() {
        return isset($this->params['ckoCardToken'])
        && !empty($this->params['ckoCardToken']);
    }

    /**
     * Checks if a public hash is available.
     */
    protected function isSavedCard() {
        return isset($this->params['publicHash'])
        && !empty($this->params['publicHash'])
        && isset($this->params['cvv'])
        && !empty($this->params['cvv']);
    }
}
