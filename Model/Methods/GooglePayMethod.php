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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Methods;

use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use Checkout\Payments\BillingDescriptor;
use Checkout\Payments\Request\PaymentRequest;
use Checkout\Payments\Request\Source\RequestTokenSource;
use Checkout\Payments\ThreeDsRequest;
use Checkout\Tokens\GooglePayTokenData;
use Checkout\Tokens\GooglePayTokenRequest;
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
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class GooglePayMethod
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
    protected $_code = self::CODE;
    /**
     * $_canAuthorize field
     *
     * @var bool $_canAuthorize
     */
    protected $_canAuthorize = true;
    /**
     * $_canCapture field
     *
     * @var bool $_canCapture
     */
    protected $_canCapture = true;
    /**
     * $_canCapturePartial field
     *
     * @var bool $_canCapturePartial
     */
    protected $_canCapturePartial = true;
    /**
     * $_canVoid field
     *
     * @var bool $_canVoid
     */
    protected $_canVoid = true;
    /**
     * $_canUseInternal field
     *
     * @var bool $_canUseInternal
     */
    protected $_canUseInternal = false;
    /**
     * $_canUseCheckout field
     *
     * @var bool $_canUseCheckout
     */
    protected $_canUseCheckout = true;
    /**
     * $_canRefund field
     *
     * @var bool $_canRefund
     */
    protected $_canRefund = true;
    /**
     * $_canRefundInvoicePartial field
     *
     * @var bool $_canRefundInvoicePartial
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * @var Json
     */
    private $json;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $ckoLogger field
     *
     * @var Logger $ckoLogger
     */
    private $ckoLogger;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    private $backendAuthSession;

    /**
     * GooglePayMethod constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param Config $config
     * @param ApiHandlerService $apiHandler
     * @param Utilities $utilities
     * @param StoreManagerInterface $storeManager
     * @param QuoteHandlerService $quoteHandler
     * @param LoggerHelper $ckoLogger
     * @param Session $backendAuthSession
     * @param DirectoryHelper $directoryHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param Json $json
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
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
        Session $backendAuthSession,
        DirectoryHelper $directoryHelper,
        DataObjectFactory $dataObjectFactory,
        Json $json,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $directoryHelper,
            $dataObjectFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->ckoLogger = $ckoLogger;
        $this->backendAuthSession = $backendAuthSession;
        $this->json = $json;
    }

    /**
     * @param array $data
     * @param float $amount
     * @param string $currency
     * @param string $reference
     *
     * @return array
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendPaymentRequest(array $data, float $amount, string $currency, string $reference = ''): array
    {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        // Get the quote
        $quote = $this->quoteHandler->getQuote();

        // Create the Google Pay data
        $googlePayData = new GooglePayTokenData();
        $googlePayData->signature = $data['cardToken']['signature'];
        $googlePayData->protocolVersion = $data['cardToken']['protocolVersion'];
        $googlePayData->signedMessage = $data['cardToken']['signedMessage'];

        // Get the token data
        $tokenData = new GooglePayTokenRequest();
        $tokenData->token_data = $googlePayData;

        // Create the Apple Pay token source
        $response = $api->getCheckoutApi()->getTokensClient()->requestWalletToken($tokenData);

        $tokenSource = new RequestTokenSource();
        $tokenSource->token = $response['token'];
        $tokenSource->billing_address = $api->createBillingAddress($quote);

        // Set the payment
        $request = new PaymentRequest();
        $request->source = $tokenSource;
        $request->currency = $currency;
        $request->processing_channel_id = $this->config->getValue('channel_id');

        // Prepare the metadata array
        $request->metadata['methodId'] = $this->_code;

        // Prepare the capture setting
        $needsAutoCapture = $this->config->needsAutoCapture();
        $request->capture = $needsAutoCapture;
        if ($needsAutoCapture) {
            $request->capture_on = $this->config->getCaptureTime();
        }

        // Set the request parameters
        $request->amount = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );

        $request->reference = $reference;
        $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';

        $theeDsRequest = new ThreeDsRequest();
        $theeDsRequest->enabled = $this->config->needs3ds($this->_code);
        $request->three_ds = $theeDsRequest;

        $request->description = __('Payment request from %1', $this->config->getStoreName())->render();
        $request->customer = $api->createCustomer($quote);
        $request->payment_type = 'Regular';
        $request->shipping = $api->createShippingAddress($quote);

        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $billingDescriptor = new BillingDescriptor();
            $billingDescriptor->city = $this->config->getValue('descriptor_city');
            $billingDescriptor->name = $this->config->getValue('descriptor_name', null, null, ScopeInterface::SCOPE_STORE);
            $request->billing_descriptor = $billingDescriptor;
        }

        // Add the quote metadata
        $request->metadata['quoteData'] = $this->json->serialize($this->quoteHandler->getQuoteRequestData($quote));

        // Add the base metadata
        $request->metadata = array_merge(
            $request->metadata,
            $this->apiHandler->getBaseMetadata()
        );

        $this->ckoLogger->additional($this->utilities->objectToArray($request), 'payment');

        // Send the charge request
        return $api->getCheckoutApi()->getPaymentsClient()->requestPayment($request);;
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function capture(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            // Check the status
            if (!$this->canCapture()) {
                throw new LocalizedException(
                    __('The capture action is not available.')
                );
            }

            // Process the capture request
            $response = $api->captureOrder($payment, (float)$amount);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The capture request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response['action_id']);
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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
            $payment->setTransactionId($response['action_id']);
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $order = $payment->getOrder();
            // Get the store code
            $storeCode = $order->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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
            $payment->setTransactionId($response['action_id']);
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function refund(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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
            $payment->setTransactionId($response['action_id']);
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
    public function isAvailable(CartInterface $quote = null): bool
    {
        if ($this->isModuleActive() && parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code) && !$this->backendAuthSession->isLoggedIn();
        }

        return false;
    }
}
