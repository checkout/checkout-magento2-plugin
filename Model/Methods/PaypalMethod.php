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
use Checkout\Payments\PaymentType;
use Checkout\Payments\Previous\PaymentRequest as PreviousPaymentRequest;
use Checkout\Payments\ProcessingSettings;
use Checkout\Payments\Request\PaymentRequest;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
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
class PaypalMethod extends AbstractMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    const CODE = 'checkoutcom_paypal';
    /**
     * $code field
     */
    protected $code = self::CODE;
    /**
     * $canAuthorize field
     */
    protected $canAuthorize = true;
    /**
     * $canCapture field
     */
    protected $canCapture = true;
    /**
     * $canCapturePartial field
     */
    protected $canCapturePartial = true;
    /**
     * $canVoid field
     */
    protected $canVoid = true;
    /**
     * $canUseInternal field
     */
    protected $canUseInternal = false;
    /**
     * $canUseCheckout field
     */
    protected $canUseCheckout = true;
    /**
     * $canRefund field
     */
    protected $canRefund = true;
    /**
     * $canRefundInvoicePartial field
     */
    protected $canRefundInvoicePartial = true;

    private Config $config;
    private ApiHandlerService $apiHandler;
    private Utilities $utilities;
    private StoreManagerInterface $storeManager;
    private QuoteHandlerService $quoteHandler;
    private LoggerHelper $ckoLogger;
    private Session $backendAuthSession;
    protected DirectoryHelper $directoryHelper;
    protected DataObjectFactory $dataObjectFactory;
    private Json $json;
    protected PaymentContextRequestService $contextService;

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
        PaymentContextRequestService $contextService,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $directoryHelper,
            $scopeConfig,
            $logger,
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
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
        $this->directoryHelper = $directoryHelper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->json = $json;
        $this->contextService = $contextService;
    }

    /**
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

        // Set the payment
        if ($this->apiHandler->isPreviousMode()) {
            $request = new PreviousPaymentRequest();
        } else {
            $request = new PaymentRequest();
        }

        // Set parameters
        $request->capture = $this->config->needsAutoCapture();
        $request->amount = $this->utilities->formatDecimals($amount * 100);
        $request->payment_context_id = $data['contextPaymentId'];
        $request->processing_channel_id = $this->config->getValue('channel_id');
        $request->reference = $reference;
        $request->metadata['methodId'] = $this->code;
        $request->description = __('Payment request from %1', $this->config->getStoreName())->render();
        $request->customer = $api->createCustomer($quote);
        $request->payment_type = PaymentType::$regular;
        $request->shipping = $api->createShippingAddress($quote);
        $request->metadata['quoteData'] = $this->json->serialize($this->quoteHandler->getQuoteRequestData($quote));
        $request->metadata = array_merge(
            $request->metadata,
            $this->apiHandler->getBaseMetadata()
        );
        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $billingDescriptor = new BillingDescriptor();
            $billingDescriptor->city = $this->config->getValue('descriptor_city');
            $billingDescriptor->name = $this->config->getValue('descriptor_name', null, null, ScopeInterface::SCOPE_STORE);
            $request->billing_descriptor = $billingDescriptor;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();
        if ($shipping->getShippingDescription() && $shipping->getShippingInclTax() > 0) {
            $processing = new ProcessingSettings();
            $processing->shipping_amount = $this->utilities->formatDecimals($shipping->getShippingInclTax() * 100);
            $request->processing = $processing;
        }

        $request->currency = $currency;

        $this->ckoLogger->additional($this->utilities->objectToArray($request), 'payment');

        // Send the charge request
        return $api->getCheckoutApi()->getPaymentsClient()->requestPayment($request);
    }

    /**
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
            try {
                $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
            } catch (CheckoutArgumentException $e) {
                if (!$this->config->isAbcRefundAfterNasMigrationActive($storeCode)) {
                    throw new LocalizedException(__($e->getMessage()));
                }
                $api = $this->apiHandler->initAbcForRefund($storeCode, ScopeInterface::SCOPE_STORE);
            }

            // Check the status
            if (!$this->canRefund()) {
                throw new LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund request
            try {
                $response = $api->refundOrder($payment, $amount);
            } catch (CheckoutApiException $e) {
                if (!$this->config->isAbcRefundAfterNasMigrationActive($storeCode)) {
                    throw new LocalizedException(__($e->getMessage()));
                }
                $api = $this->apiHandler->initAbcForRefund($storeCode, ScopeInterface::SCOPE_STORE);
                $response = $api->refundOrder($payment, $amount);
            }

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
     * @throws LocalizedException
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        if ($this->isModuleActive() && parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->code) && !$this->backendAuthSession->isLoggedIn();
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function canUseForCurrency($currencyCode): bool
    {
        $availableCurrencies = array_filter(explode(',', $this->getConfigData('specificcurrencies') ?? ''));
        if (!empty($availableCurrencies) && !in_array($currencyCode, $availableCurrencies)) {
            return false;
        }

        return true;
    }
}
