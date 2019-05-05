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
use \Checkout\Models\Payments\TokenSource;
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
     * @var Config
     */
    protected $config;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->config = $config;
        $this->utilities = $utilities;

        // Get the request parameters
        $this->params = $this->request->getParams();
    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer)
    {
        // Get the order
        $order = $observer->getEvent()->getOrder();

        // Get the method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Process the payment
        if ($this->backendAuthSession->isLoggedIn() && isset($this->params['ckoCardToken']) && $methodId == 'checkoutcom_moto') {
            // Set the token source
            $tokenSource = new TokenSource($this->params['ckoCardToken']);

            // Set the payment
            $request = new Payment(
                $tokenSource, 
                $order->getOrderCurrencyCode()
            );

            // Prepare the capture date setting
            $captureDate = $this->config->getCaptureTime($methodId);
            
            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($methodId);
            $request->amount = $order->getGrandTotal()*100;
            $request->reference = $order->getIncrementId();
            $request->payment_ip = $this->remoteAddress->getRemoteAddress();
            if ($captureDate) {
                $request->capture_time = $this->config->getCaptureTime($methodId);
            }
            
            // Send the charge request
            $response = $this->apiHandler->checkoutApi
                ->payments()
                ->request($request);

            // Process the response
            $success = $this->apiHandler->isValidResponse($response);

            //  Add the response to the order
            if ($success) {
                $this->utilities->setPaymentData($order, $response);
            }
            else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The transaction could not be processed.')
                );
            }
        }
      
        return $this;
    }
}
