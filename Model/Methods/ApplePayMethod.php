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

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Models\Tokens\ApplePay;
use \Checkout\Models\Tokens\ApplePayHeader;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\BillingDescriptor;
use \Checkout\Library\Exceptions\CheckoutHttpException;

/**
 * Class ApplePayMethod
 */
class ApplePayMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    const CODE = 'checkoutcom_apple_pay';

    /**
     * @var string
     */
    public $_code = self::CODE;

    /**
     * @var bool
     */
    public $_canAuthorize = true;

    /**
     * @var bool
     */
    public $_canCapture = true;

    /**
     * @var bool
     */
    public $_canCancel = true;

    /**
     * @var bool
     */
    public $_canCapturePartial = true;

    /**
     * @var bool
     */
    public $_canVoid = true;

    /**
     * @var bool
     */
    public $_canUseInternal = false;

    /**
     * @var bool
     */
    public $_canUseCheckout = true;

    /**
     * @var bool
     */
    public $_canRefund = true;

    /**
     * @var bool
     */
    public $_canRefundInvoicePartial = true;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Logger
     */
    public $ckoLogger;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * ApplePayMethod constructor.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\apiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Helper\Logger $ckoLogger,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder         = $urlBuilder;
        $this->backendAuthSession = $backendAuthSession;
        $this->cart               = $cart;
        $this->_objectManager     = $objectManager;
        $this->invoiceSender      = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
        $this->checkoutData       = $checkoutData;
        $this->quoteRepository    = $quoteRepository;
        $this->quoteManagement    = $quoteManagement;
        $this->orderSender        = $orderSender;
        $this->sessionQuote       = $sessionQuote;
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
        $this->utilities          = $utilities;
        $this->storeManager       = $storeManager;
        $this->ckoLogger          = $ckoLogger;
        $this->quoteHandler       = $quoteHandler;
    }

    /**
     * Send a charge request.
     */
    public function sendPaymentRequest($data, $amount, $currency, $reference = '')
    {
        try {
            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Get the quote
            $quote = $this->quoteHandler->getQuote();

            // Create the Apple Pay header
            $applePayHeader = new ApplePayHeader(
                $data['cardToken']['paymentData']['header']['transactionId'],
                $data['cardToken']['paymentData']['header']['publicKeyHash'],
                $data['cardToken']['paymentData']['header']['ephemeralPublicKey']
            );

            // Create the Apple Pay data instance
            $applePayData = new ApplePay(
                $data['cardToken']['paymentData']['version'],
                $data['cardToken']['paymentData']['signature'],
                $data['cardToken']['paymentData']['data'],
                $applePayHeader
            );

            // Get the token data
            $tokenData = $api->checkoutApi
                ->tokens()
                ->request($applePayData);

            // Create the Apple Pay token source
            $tokenSource = new TokenSource($tokenData->getId());

            // Set the payment
            $request = new Payment(
                $tokenSource,
                $currency
            );

            // Prepare the metadata array
            $request->metadata['methodId'] = $this->_code;
            $request->metadata['isFrontendRequest'] = true;

            // Prepare the metadata array
            $request->metadata = array_merge(
                $request->metadata,
                $this->apiHandler->getBaseMetadata()
            );

            // Prepare the capture date setting
            $captureDate = $this->config->getCaptureTime($this->_code);

            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($this->_code);
            $request->amount = $this->quoteHandler->amountToGateway(
                $this->utilities->formatDecimals($amount),
                $quote
            );
            $request->reference = $reference;
            $request->description = __('Payment request from %1', $this->config->getStoreName())->getText();
            $request->customer = $api->createCustomer($quote);
            $request->payment_type = 'Regular';
            $request->shipping = $api->createShippingAddress($quote);
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

            // Add the quote metadata
            $request->metadata['quoteData'] = json_encode($this->quoteHandler->getQuoteRequestData($quote));

            // Send the charge request
            $response = $api->checkoutApi
                ->payments()
                ->request($request);
            
            return $response;
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
            return null;
        }
    }
    
    /**
     * Perform a void request.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     * @param float $amount The amount
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            if ($this->backendAuthSession->isLoggedIn()) {
                // Get the store code
                $storeCode = $payment->getOrder()->getStore()->getCode();

                // Initialize the API handler
                $api = $this->apiHandler->init($storeCode);

                // Check the status
                if (!$this->canRefund()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund action is not available.')
                    );
                }

                // Process the refund request
                $response = $api->refundOrder($payment, $amount);
                if (!$api->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund request could not be processed.')
                    );
                }

                // Set the transaction id from response
                $payment->setTransactionId($response->action_id);
            }
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
        } finally {
            return $this;
        }
    }
    
    /**
     * Check whether method is available
     *
     * @param  \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code)
            && !$this->backendAuthSession->isLoggedIn();
        }

        return false;
    }
}
