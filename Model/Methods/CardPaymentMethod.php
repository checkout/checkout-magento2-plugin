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
use Checkout\Payments\Previous\PaymentRequest as PreviousPaymentRequest;
use Checkout\Payments\Previous\Source\RequestTokenSource as PreviousRequestTokenSource;
use Checkout\Payments\Request\PaymentRequest;
use Checkout\Payments\Request\Source\RequestTokenSource;
use Checkout\Payments\ThreeDsRequest;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Model\Session as CustomerModelSession;
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
 * Class CardPaymentMethod
 */
class CardPaymentMethod extends AbstractMethod
{
    /**
     * CODE constant
     */
    const CODE = 'checkoutcom_card_payment';

    /**
     * PREFERRED_SCHEMES constant
     */
    const PREFERRED_SCHEMES = ['VISA', 'MASTERCARD', 'CARTES_BANCAIRES'];

    /**
     * $code field
     *
     * @var string $code
     */
    protected $code = self::CODE;
    /**
     * $canAuthorize field
     *
     * @var bool $canAuthorize
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
    private Session $backendAuthSession;
    private CustomerModelSession $customerSession;
    private Config $config;
    private ApiHandlerService $apiHandler;
    private Utilities $utilities;
    private StoreManagerInterface $storeManager;
    private QuoteHandlerService $quoteHandler;
    private CardHandlerService $cardHandler;
    private LoggerHelper $ckoLogger;
    protected Json $json;

    public function __construct(
        Session $backendAuthSession,
        CustomerModelSession $customerSession,
        Config $config,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        CardHandlerService $cardHandler,
        LoggerHelper $ckoLogger,
        Json $json,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        DirectoryHelper $directoryHelper,
        DataObjectFactory $dataObjectFactory,
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
        $this->backendAuthSession = $backendAuthSession;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->cardHandler = $cardHandler;
        $this->ckoLogger = $ckoLogger;
        $this->json = $json;
    }

    /**
     * Send a charge request
     *
     * @param array $data
     * @param float $amount
     * @param string $currency
     * @param string $reference
     * @param CartInterface|null $quote
     * @param bool|null $isApiOrder
     * @param $customerId
     *
     * @return array
     * @throws CheckoutApiException
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException|CheckoutArgumentException
     */
    public function sendPaymentRequest(
        array $data,
        float $amount,
        string $currency,
        string $reference = '',
        ?CartInterface $quote = null,
        ?bool $isApiOrder = null,
        $customerId = null
    ): array {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        if (!$quote) {
            // Get the quote
            $quote = $this->quoteHandler->getQuote();
        }

        // Set the token source
        if ($this->apiHandler->isPreviousMode()) {
            $tokenSource = new PreviousRequestTokenSource();
        } else {
            $tokenSource = new RequestTokenSource();
        }

        $tokenSource->token = $data['cardToken'];
        $tokenSource->billing_address = $api->createBillingAddress($quote);

        // Set the payment
        if ($this->apiHandler->isPreviousMode()) {
            $request = new PreviousPaymentRequest();
        } else {
            $request = new PaymentRequest();
        }

        $request->currency = $currency;
        $request->source = $tokenSource;
        $request->processing_channel_id = $this->config->getValue('channel_id');

        // Prepare the metadata array
        $request->metadata['methodId'] = $this->code;

        // Prepare the capture setting
        $madaEnabled = $this->config->getValue('mada_enabled', $this->code);

        if (isset($data['cardBin']) && $this->cardHandler->isMadaBin($data['cardBin']) && $madaEnabled) {
            $request->metadata['udf1'] = 'MADA';
        } else {
            $needsAutoCapture = $this->config->needsAutoCapture();
            $request->capture = $needsAutoCapture;
            if ($needsAutoCapture) {
                $request->capture_on = $this->config->getCaptureTime();
            }
        }

        // Prepare the save card setting
        $saveCardEnabled = $this->config->getValue('save_card_option', $this->code);

        // Set the request parameters
        $request->amount = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $request->reference = $reference;
        $request->success_url = $this->getSuccessUrl($data, $isApiOrder);
        $request->failure_url = $this->getFailureUrl($data, $isApiOrder);

        $theeDsRequest = new ThreeDsRequest();
        $theeDsRequest->enabled = $this->config->needs3ds($this->code);
        $theeDsRequest->attempt_n3d = (bool)$this->config->getValue('attempt_n3d', $this->code);

        $request->three_ds = $theeDsRequest;
        $request->description = __('Payment request from %1', $this->config->getStoreName())->render();
        $request->customer = $api->createCustomer($quote);
        $request->payment_type = 'Regular';

        if (!$quote->getIsVirtual()) {
            $request->shipping = $api->createShippingAddress($quote);
        }

        // Preferred scheme
        if (isset($data['preferredScheme']) && in_array((string)strtoupper($data['preferredScheme']), self::PREFERRED_SCHEMES)) {
            $request->processing = ['preferred_scheme' => strtolower($data['preferredScheme'])];
        }

        // Save card check
        if ($isApiOrder) {
            if (isset($data['successUrl'])) {
                $request->metadata['successUrl'] = $this->getSuccessUrl($data);
            }

            if (isset($data['failureUrl'])) {
                $request->metadata['failureUrl'] = $this->getFailureUrl($data);
            }

            if (isset($data['saveCard']) && $data['saveCard'] === true && $saveCardEnabled) {
                $request->metadata['saveCard'] = 1;
                $request->metadata['customerId'] = $customerId;
            }
        } else {
            if (isset($data['saveCard']) && $this->json->unserialize($data['saveCard']) === true && $saveCardEnabled && $this->customerSession->isLoggedIn()) {
                $request->metadata['saveCard'] = 1;
                $request->metadata['customerId'] = $this->customerSession->getCustomer()->getId();
            }
        }

        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $billingDescriptor = new BillingDescriptor();
            $billingDescriptor->city = $this->config->getValue('descriptor_city');
            $billingDescriptor->name = $this->config->getValue('descriptor_name', null, null, ScopeInterface::SCOPE_STORE);

            $request->billing_descriptor = $billingDescriptor;
        }

        // Add the quote metadata
        $request->metadata['quoteData'] = $this->json->serialize(
            $this->quoteHandler->getQuoteRequestData($quote)
        );

        // Add the base metadata
        $request->metadata = array_merge(
            $request->metadata,
            $this->apiHandler->getBaseMetadata()
        );

        $this->ckoLogger->additional($this->utilities->objectToArray($request), 'payment');

        // Send the charge request
        return $api->getCheckoutApi()->getPaymentsClient()->requestPayment($request);
    }

    /**
     * Perform a capture request
     *
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
            try {
                $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
            } catch (CheckoutArgumentException $e) {
                if (!$this->config->isAbcRefundAfterNasMigrationActive($storeCode)) {
                    throw new LocalizedException(__($e->getMessage()));
                }
                $api = $this->apiHandler->initAbcForRefund($storeCode, ScopeInterface::SCOPE_STORE);
            }

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
     * Perform a void request
     *
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
     * Perform a void request on order cancel
     *
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
            // Get the store code
            $order = $payment->getOrder();
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
     * Perform a refund request
     *
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
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        if ($this->isModuleActive() && parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->code) && !$this->backendAuthSession->isLoggedIn();
        }

        return false;
    }
}
