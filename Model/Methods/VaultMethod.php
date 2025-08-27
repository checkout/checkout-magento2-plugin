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
use Checkout\Payments\Request\Source\RequestIdSource;
use Checkout\Payments\ThreeDsRequest;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
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
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class VaultMethod
 */
class VaultMethod extends AbstractMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    const CODE = 'checkoutcom_vault';
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
    protected $canUseInternal = true;
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
    protected DirectoryHelper $directoryHelper;
    protected DataObjectFactory $dataObjectFactory;
    private Session $backendAuthSession;
    private Config $config;
    private ApiHandlerService $apiHandler;
    private Utilities $utilities;
    private StoreManagerInterface $storeManager;
    private VaultHandlerService $vaultHandler;
    private CardHandlerService $cardHandler;
    private QuoteHandlerService $quoteHandler;
    private LoggerHelper $ckoLogger;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Session $backendAuthSession,
        Config $config,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        StoreManagerInterface $storeManager,
        VaultHandlerService $vaultHandler,
        CardHandlerService $cardHandler,
        QuoteHandlerService $quoteHandler,
        LoggerHelper $ckoLogger,
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
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->storeManager = $storeManager;
        $this->vaultHandler = $vaultHandler;
        $this->cardHandler = $cardHandler;
        $this->quoteHandler = $quoteHandler;
        $this->ckoLogger = $ckoLogger;
    }

    /**
     * Sends a payment request
     *
     * @param string[] $data
     * @param float $amount
     * @param string $currency
     * @param string $reference
     * @param CartInterface|null $quote
     * @param bool|null $isApiOrder
     * @param mixed|null $customerId
     * @param bool|null $isInstantPurchase
     *
     * @return mixed|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws FileSystemException
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     */
    public function sendPaymentRequest(
        array $data,
        float $amount,
        string $currency,
        string $reference = '',
        ?CartInterface $quote = null,
        ?bool $isApiOrder = null,
        $customerId = null,
        ?bool $isInstantPurchase = null
    ) {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        // Get the quote
        $quote = $this->quoteHandler->getQuote();

        // Find the card token
        if (isset($isApiOrder) && isset($customerId)) {
            $card = $this->vaultHandler->getCardFromHash($data['publicHash'], $customerId);
        } else {
            $card = $this->vaultHandler->getCardFromHash($data['publicHash']);
        }

        // Set the token source
        $idSource = new RequestIdSource();
        $idSource->id = $card->getGatewayToken();

        // Check CVV config
        if ($this->config->getValue('require_cvv', $this->code)) {
            if (!isset($data['cvv']) || (int)$data['cvv'] == 0) {
                throw new LocalizedException(__('The CVV value is required.'));
            } else {
                $idSource->cvv = $data['cvv'];
            }
        }

        // Set the payment
        $request = new PaymentRequest();

        $request->currency = $currency;
        $request->source = $idSource;
        $request->processing_channel_id = $this->config->getValue('channel_id');

        // Prepare the metadata array
        $request->metadata['methodId'] = $this->code;

        if ($isApiOrder) {
            if (isset($data['successUrl'])) {
                $request->metadata['successUrl'] = $this->getSuccessUrl($data);
            }

            if (isset($data['failureUrl'])) {
                $request->metadata['failureUrl'] = $this->getFailureUrl($data);
            }
        }

        // Prepare the capture setting
        $needsAutoCapture = $this->config->needsAutoCapture();
        $request->capture = $needsAutoCapture;
        if ($needsAutoCapture) {
            $request->capture_on = $this->config->getCaptureTime();
        }

        // Prepare the MADA setting
        $madaEnabled = (bool)$this->config->getValue('mada_enabled', $this->code);

        // Set the request parameters
        $request->amount = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $request->reference = $reference;
        $theeDsRequest = new ThreeDsRequest();

        if ($isInstantPurchase) {
            $theeDsRequest->enabled = false;
        } else {
            $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
            $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
            $theeDsRequest->enabled = $this->config->needs3ds($this->code);
            $theeDsRequest->attempt_n3d = (bool)$this->config->getValue(
                'attempt_n3d',
                $this->code,
                null,
                ScopeInterface::SCOPE_WEBSITE
            );
        }

        $request->three_ds = $theeDsRequest;

        $request->description = __('Payment request from %1', $this->config->getStoreName())->render();
        $request->payment_type = 'Regular';
        if (!$quote->getIsVirtual()) {
            $request->shipping = $api->createShippingAddress($quote);
        }

        // Mada BIN Check
        if (isset($data['cardBin']) && $this->cardHandler->isMadaBin($data['cardBin']) && $madaEnabled) {
            $request->metadata = ['udf1' => 'MADA'];
        }

        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $billingDescriptor = new BillingDescriptor();
            $billingDescriptor->city = $this->config->getValue('descriptor_city');
            $billingDescriptor->name = $this->config->getValue('descriptor_name', null, null, ScopeInterface::SCOPE_STORE);
            $request->billing_descriptor = $billingDescriptor;
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
        return $api->getCheckoutApi()->getPaymentsClient()->requestPayment($request);
    }

    /**
     * Perform a capture request
     *
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return $this|VaultMethod
     * @throws LocalizedException
     * @throws CheckoutArgumentException
     * @throws CheckoutApiException
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
     * Perform a void request
     *
     * @param InfoInterface $payment
     *
     * @return $this|VaultMethod
     * @throws LocalizedException
     * @throws CheckoutArgumentException
     * @throws CheckoutApiException
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
     * @return $this|VaultMethod
     * @throws LocalizedException
     * @throws CheckoutArgumentException
     * @throws CheckoutApiException
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
     * Perform a refund request
     *
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return $this|VaultMethod
     * @throws LocalizedException
     * @throws CheckoutArgumentException
     * @throws CheckoutApiException
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
                throw new LocalizedException(__($e->getMessage()));
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
                throw new LocalizedException(__($e->getMessage()));
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
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        return $this->isModuleActive() && $this->config->getValue(
                'active',
                $this->code,
                null,
                ScopeInterface::SCOPE_WEBSITE
            ) && $this->vaultHandler->userHasCards() && !$this->backendAuthSession->isLoggedIn();
    }
}
