<?php
/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Payment\Transaction;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\BillingDescriptor;
use \Checkout\Models\Payments\Capture;

/**
 * Class OrderSaveBefore.
 */
class OrderSaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    protected $backendAuthSession;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

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
     * @var TransactionHandlerService
     */
    protected $transactionHandler;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Logger
     */
    protected $logger;

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
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->vaultHandler = $vaultHandler;
        $this->transactionHandler = $transactionHandler;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
    }

    /**
     * OrderSaveBefore constructor.
     */
    public function execute(Observer $observer)
    {
        // Get the request parameters
        $this->params = $this->request->getParams();

        // Get the order
        $this->order = $observer->getEvent()->getOrder();

        // Get the payment
        $payment = $this->order->getPayment();

        // Get the store code
        $storeCode = $this->order->getStore()->getCode();

        // Get the method id
        $this->methodId = $payment->getMethodInstance()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

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
            $request->metadata = array_merge(
                ['methodId' => $this->methodId],
                $this->apiHandler->getBaseMetadata()
            );

            // Prepare the capture date setting
            $captureDate = $this->config->getCaptureTime($this->methodId);

            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($this->methodId);
            $request->amount = $this->prepareAmount();
            $request->reference = $this->order->getIncrementId();
            $request->payment_type = 'MOTO';
            $request->shipping = $api->createShippingAddress($this->order);
            if ($captureDate) {
                $request->capture_on = $this->config->getCaptureTime();
            }

            // Billing descriptor
            if ($this->config->needsDynamicDescriptor()) {
                $request->billing_descriptor = new BillingDescriptor(
                    $this->config->getValue('descriptor_name'),
                    $this->config->getValue('descriptor_city')
                );
            }

            // Send the charge request
            $response = $api->checkoutApi->payments()->request($request);

            // Logging
            $this->logger->display($response);

            // Add the response to the order
            if ($api->isValidResponse($response)) {
                $this->utilities->setPaymentData($this->order, $response);
                $this->messageManager->addSuccessMessage(
                    __('The payment request was successfully processed.')
                );
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The transaction could not be processed. Please check the payment details.')
                );
            }
        }
        else if ($this->needsBackendCapture()) {
            // Get the payment info
            $paymentInfo = $this->utilities->getPaymentData($this->order);

            // Prepare the request
            $request = new Capture($paymentInfo['id']);
            $request->amount = $this->prepareAmount();
            
            // Add the backend capture flag
            $request->metadata['isBackendCapture'] = true;

            // Process the request
            $response = $api->checkoutApi->payments()->capture($request);

            // Logging
            $this->logger->display($response);

            // Process the capture request
            if ($api->isValidResponse($response)) {
                $this->utilities->setPaymentData($this->order, $response);
                $this->messageManager->addSuccessMessage(
                    __('The capture request was successfully processed.')
                );
            }
            else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The capture request could not be processed.')
                );
            }
        }

        return $this;
    }

    /**
     * Prepare the payment amount for the payment request.
     */
    protected function prepareAmount()
    {
        // Get the payment instance
        $payment = $this->order->getPayment();

        // Return the formatted amount
        return $this->orderHandler->amountToGateway(
            $this->utilities->formatDecimals($payment->getAmountPaid()),
            $this->order
        );
    }

    /**
     * Checks if the MOTO logic should be triggered.
     */
    protected function needsMotoProcessing()
    {
        return $this->backendAuthSession->isLoggedIn()
        && isset($this->params['ckoCardToken'])
        && $this->methodId == 'checkoutcom_moto'
        && !$this->transactionHandler->hasTransaction(
            Transaction::TYPE_AUTH,
            $this->order
        );
    }

    /**
     * Checks if the backend capture logic should be triggered.
     */
    protected function needsBackendCapture()
    {
        // Get the payment instance
        $payment = $this->order->getPayment();

        // Return the test
        return $this->backendAuthSession->isLoggedIn()
        && ($payment->canCapturePartial() || $payment->canCapture())
        && $this->transactionHandler->hasTransaction(
            Transaction::TYPE_AUTH,
            $this->order
        );
    }

    /**
     * Provide a source from request.
     */
    protected function getSource()
    {
        if ($this->isCardToken()) {
            // Get the store code
            $storeCode = $this->order->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Create the token source
            $tokenSource = new TokenSource($this->params['ckoCardToken']);
            $tokenSource->billing_address = $api->createBillingAddress($this->order);

            return $tokenSource;
        } elseif ($this->isSavedCard()) {
            $card = $this->vaultHandler->getCardFromHash(
                $this->params['publicHash'],
                $this->order->getCustomerId()
            );
            $idSource = new IdSource($card->getGatewayToken());
            $idSource->cvv = $this->params['cvv'];

            return $idSource;
        } else {
            $this->messageManager->addErrorMessage(
                __('Please provide the required card information for the MOTO payment.')
            );
        }
    }

    /**
     * Checks if a card token is available.
     */
    protected function isCardToken()
    {
        return isset($this->params['ckoCardToken'])
        && !empty($this->params['ckoCardToken']);
    }

    /**
     * Checks if a public hash is available.
     */
    protected function isSavedCard()
    {
        return isset($this->params['publicHash'])
        && !empty($this->params['publicHash'])
        && isset($this->params['cvv'])
        && !empty($this->params['cvv']);
    }
}
