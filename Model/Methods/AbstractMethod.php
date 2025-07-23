<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
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

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Block\Form;
use Magento\Payment\Block\Info;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Payment method abstract model
 *
 * @category  Magento2
 * @package   Checkout.com
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @see \Magento\Payment\Model\Method\Adapter
 * @see https://devdocs.magento.com/guides/v2.1/payments-integrations/payment-gateway/payment-gateway-intro.html
 * @since 100.0.2
 */
abstract class AbstractMethod extends AbstractExtensibleModel implements MethodInterface, PaymentMethodInterface
{
    /**
     * STATUS_UNKNOWN constant
     */
    const string STATUS_UNKNOWN = 'UNKNOWN';
    /**
     * STATUS_APPROVED constant
     */
    const string STATUS_APPROVED = 'APPROVED';
    /**
     * STATUS_ERROR constant
     */
    const string STATUS_ERROR = 'ERROR';
    /**
     * STATUS_DECLINED constant
     */
    const string STATUS_DECLINED = 'DECLINED';
    /**
     * STATUS_VOID constant
     */
    const string STATUS_VOID = 'VOID';
    /**
     * STATUS_SUCCESS constant
     */
    const string STATUS_SUCCESS = 'SUCCESS';
    /**
     * $code field
     *
     * @var string $code
     */
    protected string $code;
    /**
     * $formBlockType field
     *
     * @var string $formBlockType
     */
    protected string $formBlockType = Form::class;
    /**
     * $infoBlockType field
     *
     * @var string $infoBlockType
     */
    protected string $infoBlockType = Info::class;
    /**
     * Payment Method feature
     *
     * @var bool $isGateway
     */
    protected bool $isGateway = false;
    /**
     * Payment Method feature
     *
     * @var bool $isOffline
     */
    protected bool $isOffline = false;
    /**
     * Payment Method feature
     *
     * @var bool $canOrder
     */
    protected bool $canOrder = false;
    /**
     * Payment Method feature
     *
     * @var bool $canAuthorize
     */
    protected bool $canAuthorize = false;
    /**
     * Payment Method feature
     *
     * @var bool $canCapture
     */
    protected bool $canCapture = false;
    /**
     * Payment Method feature
     *
     * @var bool $canCapturePartial
     */
    protected bool $canCapturePartial = false;
    /**
     * Payment Method feature
     *
     * @var bool $canCaptureOnce
     */
    protected bool $canCaptureOnce = false;
    /**
     * Payment Method feature
     *
     * @var bool $canRefund
     */
    protected bool $canRefund = false;
    /**
     * Payment Method feature
     *
     * @var bool $canRefundInvoicePartial
     */
    protected bool $canRefundInvoicePartial = false;
    /**
     * Payment Method feature
     *
     * @var bool $canVoid
     */
    protected bool $canVoid = false;
    /**
     * Payment Method feature
     *
     * @var bool $canUseInternal
     */
    protected bool $canUseInternal = true;
    /**
     * Payment Method feature
     *
     * @var bool $canUseCheckout
     */
    protected bool $canUseCheckout = true;
    /**
     * Payment Method feature
     *
     * @var bool $isInitializeNeeded
     */
    protected bool $isInitializeNeeded = false;
    /**
     * Payment Method feature
     *
     * @var bool $canFetchTransactionInfo
     */
    protected bool $canFetchTransactionInfo = false;
    /**
     * Payment Method feature
     *
     * @var bool $canReviewPayment
     */
    protected bool $canReviewPayment = false;
    /**
     * TODO: whether a captured transaction may be voided by this gateway
     * This may happen when amount is captured, but not settled
     *
     * @var bool $canCancelInvoice
     */
    protected bool $canCancelInvoice = false;
    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array $debugReplacePrivateDataKeys
     */
    protected array $debugReplacePrivateDataKeys = [];
    /**
     * $data field
     *
     * @var array $data
     */
    private array $data;

    /**
     * @param Config $config
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param DirectoryHelper $directory
     * @param DataObjectFactory $dataObjectFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private Config $config,
        private DirectoryHelper $directory,
        protected ScopeConfigInterface $scopeConfig,
        protected Logger $logger,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        protected Data $paymentData,
        protected DataObjectFactory $dataObjectFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->data = $data;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $storeId
     *
     * @return void
     */
    public function setStore($storeId): void
    {
        $this->setData('store', (int)$storeId);
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canCapturePartial(): bool
    {
        return $this->canCapturePartial;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canCaptureOnce(): bool
    {
        return $this->canCaptureOnce;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canRefundPartialPerInvoice(): bool
    {
        return $this->canRefundInvoicePartial;
    }

    /**
     * {@inheritDoc}
     *
     * Can be used in admin.
     *
     * @return bool
     */
    public function canUseInternal(): bool
    {
        return $this->canUseInternal;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function canUseCheckout(): bool
    {
        return $this->canUseCheckout;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canEdit(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canFetchTransactionInfo(): bool
    {
        return $this->canFetchTransactionInfo;
    }

    /**
     * {@inheritDoc}
     *
     * @param InfoInterface $payment
     * @param string $transactionId
     *
     * @return mixed[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @api
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function isGateway(): bool
    {
        return $this->isGateway;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function isOffline(): bool
    {
        return $this->isOffline;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function isInitializeNeeded(): bool
    {
        return $this->isInitializeNeeded;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $currencyCode
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canUseForCurrency($currencyCode): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getFormBlockType(): string
    {
        if (!empty($this->data['formBlockType'])) {
            $this->formBlockType = $this->data['formBlockType'];
        }

        return $this->formBlockType;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     * @api
     */
    public function getInfoBlockType(): string
    {
        return $this->infoBlockType;
    }

    /**
     * {@inheritDoc}
     *
     * @param InfoInterface $info
     *
     * @return void
     * @api
     */
    public function setInfoInstance(InfoInterface $info): void
    {
        $this->setData('info_instance', $info);
    }

    /**
     * {@inheritDoc}
     *
     * @return AbstractMethod
     * @throws LocalizedException
     * @api
     */
    public function validate(): AbstractMethod
    {
        /**
         * to validate payment method is allowed for billing country or not
         */
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Payment) {
            $billingCountry = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
        }
        $billingCountry = $billingCountry ?: $this->directory->getDefaultCountry();

        if (!$this->canUseForCountry($billingCountry)) {
            throw new LocalizedException(
                __('You can\'t use the payment type you selected to make payments to the billing country.')
            );
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return InfoInterface
     * @throws LocalizedException
     * @api
     */
    public function getInfoInstance(): InfoInterface
    {
        $instance = $this->getData('info_instance');
        if (!$instance instanceof InfoInterface) {
            throw new LocalizedException(
                __('We cannot retrieve the payment information object instance.')
            );
        }

        return $instance;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $country
     *
     * @return bool
     * @throws LocalizedException
     */
    public function canUseForCountry($country): bool
    {
        /*
        for specific country, the flag will set up as 1
        */
        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry') ?? '');
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $field
     * @param int|string|null|Store $storeId
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . $this->getCode() . '/' . $field;

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * {@inheritDoc}
     *
     * @return int|mixed|null
     */
    public function getStore()
    {
        return $this->getData('store');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCode(): string
    {
        if (empty($this->code)) {
            throw new LocalizedException(
                __('We cannot retrieve the payment method code.')
            );
        }

        return $this->code;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     *
     * @return AbstractMethod
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function order(InfoInterface $payment, $amount): AbstractMethod
    {
        if (!$this->canOrder()) {
            throw new LocalizedException(__('The order action is not available.'));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canOrder(): bool
    {
        return $this->canOrder;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     *
     * @return AbstractMethod
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authorize(InfoInterface $payment, $amount): AbstractMethod
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canAuthorize(): bool
    {
        return $this->canAuthorize;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     *
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(InfoInterface $payment, $amount): AbstractMethod
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canCapture(): bool
    {
        return $this->canCapture;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float|string $amount
     *
     * @return AbstractMethod
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(InfoInterface $payment, $amount): AbstractMethod
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canRefund(): bool
    {
        return $this->canRefund;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     *
     * @return AbstractMethod
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cancel(InfoInterface $payment): AbstractMethod
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     *
     * @return AbstractMethod
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function void(InfoInterface $payment): AbstractMethod
    {
        if (!$this->canVoid()) {
            throw new LocalizedException(__('The void action is not available.'));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @internal param \Magento\Framework\DataObject $payment
     * @api
     */
    public function canVoid(): bool
    {
        return $this->canVoid;
    }

    /**
     * {@inheritDoc}
     *
     * @param InfoInterface $payment
     *
     * @return false
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function acceptPayment(InfoInterface $payment): bool
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canReviewPayment(): bool
    {
        return $this->canReviewPayment;
    }

    /**
     * {@inheritDoc}
     *
     * @param InfoInterface $payment
     *
     * @return false
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function denyPayment(InfoInterface $payment): bool
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getTitle(): ?string
    {
        return $this->getConfigData('title');
    }

    /**
     * {@inheritDoc}
     *
     * @param array|DataObject $data
     *
     * @return AbstractMethod
     * @throws LocalizedException
     * @api
     */
    public function assignData(DataObject $data): AbstractMethod
    {
        $this->_eventManager->dispatch('payment_method_assign_data_' . $this->getCode(), [
            AbstractDataAssignObserver::METHOD_CODE => $this,
            AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
            AbstractDataAssignObserver::DATA_CODE => $data,
        ]);

        $this->_eventManager->dispatch('payment_method_assign_data', [
            AbstractDataAssignObserver::METHOD_CODE => $this,
            AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
            AbstractDataAssignObserver::DATA_CODE => $data,
        ]);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = $this->dataObjectFactory->create();
        $checkResult->setData('is_available', true);

        // for future use in observers
        $this->_eventManager->dispatch('payment_method_is_active', [
            'result' => $checkResult,
            'method_instance' => $this,
            'quote' => $quote,
        ]);

        return $checkResult->getData('is_available');
    }

    /**
     * {@inheritDoc}
     *
     * @param int|null $storeId
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isActive($storeId = null): bool
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return AbstractMethod
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @api
     */
    public function initialize($paymentAction, $stateObject): AbstractMethod
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     * @throws LocalizedException
     * @api
     */
    public function getConfigPaymentAction(): ?string
    {
        return $this->getConfigData('payment_action');
    }

    /**
     * Used to call debug method from not Payment Method context
     *
     * @param mixed[] $debugData
     *
     * @return void
     * @throws LocalizedException
     * @api
     */
    public function debugData(array $debugData): void
    {
        $this->_debug($debugData);
    }

    /**
     * Log debug data to file
     *
     * @param mixed[] $debugData
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _debug(array $debugData): void
    {
        $this->logger->debug(
            $debugData,
            $this->getDebugReplacePrivateDataKeys(),
            $this->getDebugFlag()
        );
    }

    /**
     * Return replace keys for debug data
     *
     * @return array
     */
    public function getDebugReplacePrivateDataKeys(): array
    {
        return (array)$this->debugReplacePrivateDataKeys;
    }

    /**
     * Define if debugging is enabled
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @throws LocalizedException
     * @api
     */
    public function getDebugFlag(): bool
    {
        return (bool)(int)$this->getConfigData('debug');
    }

    /**
     * Get the success redirection URL for the payment request
     *
     * @param string[] $data
     * @param bool|null $isApiOrder
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSuccessUrl(array $data, ?bool $isApiOrder = null): string
    {
        if (isset($data['successUrl']) && !$isApiOrder) {
            return $data['successUrl'];
        }

        return $this->config->getStoreUrl() . 'checkout_com/payment/verify';
    }

    /**
     * Get the failure redirection URL for the payment request
     *
     * @param string[] $data
     * @param bool|null $isApiOrder
     *
     * @return string
     */
    public function getFailureUrl(array $data, ?bool $isApiOrder = null): string
    {
        if (isset($data['failureUrl']) && !$isApiOrder) {
            return $data['failureUrl'];
        }

        return $this->config->getStoreUrl() . 'checkout_com/payment/fail';
    }

    /**
     * Initializes injected data
     *
     * @param mixed[] $data
     *
     * @return void
     */
    protected function initializeData(array $data = []): void
    {
        if (!empty($data['formBlockType'])) {
            $this->_formBlockType = $data['formBlockType'];
        }
    }

    /**
     * Description isModuleActive function
     *
     * @return bool
     */
    public function isModuleActive(): bool
    {
        return (bool)$this->scopeConfig->getValue('settings/checkoutcom_configuration/active', ScopeInterface::SCOPE_WEBSITE);
    }
}
