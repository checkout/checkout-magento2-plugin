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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Methods;

use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Models\Payments\BillingDescriptor;
use Checkout\Models\Payments\Payment;
use Checkout\Models\Payments\TokenSource;
use Checkout\Models\Tokens\GooglePay;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Backend\Model\Auth\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class GooglePayMethod
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class GooglePayMethod extends AbstractMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    const CODE = 'checkoutcom_google_pay';
    /**
     * $_code field
     *
     * @var string $_code
     */
    public $_code = self::CODE;
    /**
     * $_canAuthorize field
     *
     * @var bool $_canAuthorize
     */
    public $_canAuthorize = true;
    /**
     * $_canCapture field
     *
     * @var bool $_canCapture
     */
    public $_canCapture = true;
    /**
     * $_canCancel field
     *
     * @var bool $_canCancel
     */
    public $_canCancel = true;
    /**
     * $_canCapturePartial field
     *
     * @var bool $_canCapturePartial
     */
    public $_canCapturePartial = true;
    /**
     * $_canVoid field
     *
     * @var bool $_canVoid
     */
    public $_canVoid = true;
    /**
     * $_canUseInternal field
     *
     * @var bool $_canUseInternal
     */
    public $_canUseInternal = false;
    /**
     * $_canUseCheckout field
     *
     * @var bool $_canUseCheckout
     */
    public $_canUseCheckout = true;
    /**
     * $_canRefund field
     *
     * @var bool $_canRefund
     */
    public $_canRefund = true;
    /**
     * $_canRefundInvoicePartial field
     *
     * @var bool $_canRefundInvoicePartial
     */
    public $_canRefundInvoicePartial = true;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    public $apiHandler;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    public $utilities;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    public $quoteHandler;
    /**
     * $ckoLogger field
     *
     * @var Logger $ckoLogger
     */
    public $ckoLogger;
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    public $messageManager;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    public $storeManager;
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    public $backendAuthSession;

    /**
     * GooglePayMethod constructor
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param Config                     $config
     * @param ApiHandlerService          $apiHandler
     * @param Utilities                  $utilities
     * @param StoreManagerInterface      $storeManager
     * @param QuoteHandlerService        $quoteHandler
     * @param LoggerHelper               $ckoLogger
     * @param ManagerInterface           $messageManager
     * @param Session                    $backendAuthSession
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     * @param DirectoryHelper            $directoryHelper
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Config $config,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        LoggerHelper $ckoLogger,
        ManagerInterface $messageManager,
        Session $backendAuthSession,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directoryHelper
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
            $data,
            $directoryHelper
        );

        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
        $this->utilities          = $utilities;
        $this->storeManager       = $storeManager;
        $this->quoteHandler       = $quoteHandler;
        $this->ckoLogger          = $ckoLogger;
        $this->messageManager     = $messageManager;
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * Send a charge request
     *
     * @param        $data
     * @param        $amount
     * @param        $currency
     * @param string $reference
     *
     * @return mixed|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws FileSystemException
     */
    public function sendPaymentRequest($data, $amount, $currency, $reference = '')
    {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        // Get the quote
        $quote = $this->quoteHandler->getQuote();

        // Create the Google Pay data
        $googlePayData = new GooglePay(
            $data['cardToken']['protocolVersion'], $data['cardToken']['signature'], $data['cardToken']['signedMessage']
        );

        // Get the token data
        $tokenData = $api->checkoutApi->tokens()->request($googlePayData);

        // Create the Apple Pay token source
        $tokenSource = new TokenSource($tokenData->getId());

        // Set the payment
        $request = new Payment(
            $tokenSource, $currency
        );

        // Prepare the metadata array
        $request->metadata['methodId'] = $this->_code;

        // Prepare the capture setting
        $needsAutoCapture = $this->config->needsAutoCapture($this->_code);
        $request->capture = $needsAutoCapture;
        if ($needsAutoCapture) {
            $request->capture_on = $this->config->getCaptureTime($this->_code);
        }

        // Set the request parameters
        $request->amount       = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $request->reference    = $reference;
        $request->description  = __('Payment request from %1', $this->config->getStoreName())->render();
        $request->customer     = $api->createCustomer($quote);
        $request->payment_type = 'Regular';
        $request->shipping     = $api->createShippingAddress($quote);

        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $request->billing_descriptor = new BillingDescriptor(
                $this->config->getValue('descriptor_name'), $this->config->getValue('descriptor_city')
            );
        }

        // Add the quote metadata
        $request->metadata['quoteData'] = json_encode($this->quoteHandler->getQuoteRequestData($quote));

        // Add the base metadata
        $request->metadata = array_merge(
            $request->metadata,
            $this->apiHandler->getBaseMetadata()
        );

        $this->ckoLogger->additional($this->utilities->objectToArray($request), 'payment');

        // Send the charge request
        try {
            $response = $api->checkoutApi->payments()->request($request);

            return $response;
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
        }
    }

    /**
     * Perform a capture request
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this|GooglePayMethod
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canCapture()) {
                throw new LocalizedException(
                    __('The capture action is not available.')
                );
            }

            // Process the capture request
            $response = $api->captureOrder($payment, $amount);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The capture request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a void request
     *
     * @param InfoInterface $payment
     *
     * @return $this|GooglePayMethod
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canVoid()) {
                throw new LocalizedException(
                    __('The void action is not available.')
                );
            }

            // Process the void request
            $response = $api->voidOrder($payment);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The void request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a void request on order cancel
     *
     * @param InfoInterface $payment
     *
     * @return $this|GooglePayMethod
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $order = $payment->getOrder();
            // Get the store code
            $storeCode = $order->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canVoid()) {
                throw new LocalizedException(
                    __('The void action is not available.')
                );
            }

            // Process the void request
            $response = $api->voidOrder($payment);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The void request could not be processed.')
                );
            }

            $comment = __(
                'Canceled order online, the voided amount is %1.',
                $order->formatPriceTxt($order->getGrandTotal())
            );
            $payment->setMessage($comment);
            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a refund request
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this|GooglePayMethod
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canRefund()) {
                throw new LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund request
            $response = $api->refundOrder($payment, $amount);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The refund request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Check whether method is available
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code) && !$this->backendAuthSession->isLoggedIn();
        }

        return false;
    }
}
