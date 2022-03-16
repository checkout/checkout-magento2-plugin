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
     *
     * @var string STATUS_UNKNOWN
     */
    const STATUS_UNKNOWN = 'UNKNOWN';
    /**
     * STATUS_APPROVED constant
     *
     * @var string STATUS_APPROVED
     */
    const STATUS_APPROVED = 'APPROVED';
    /**
     * STATUS_ERROR constant
     *
     * @var string STATUS_ERROR
     */
    const STATUS_ERROR = 'ERROR';
    /**
     * STATUS_DECLINED constant
     *
     * @var string STATUS_DECLINED
     */
    const STATUS_DECLINED = 'DECLINED';
    /**
     * STATUS_VOID constant
     *
     * @var string STATUS_VOID
     */
    const STATUS_VOID = 'VOID';
    /**
     * STATUS_SUCCESS constant
     *
     * @var string STATUS_SUCCESS
     */
    const STATUS_SUCCESS = 'SUCCESS';
    /**
     * $_code field
     *
     * @var string $_code
     */
    protected $_code;
    /**
     * $_formBlockType field
     *
     * @var string $_formBlockType
     */
    protected $_formBlockType = Form::class;
    /**
     * $_infoBlockType field
     *
     * @var string $_infoBlockType
     */
    protected $_infoBlockType = Info::class;
    /**
     * Payment Method feature
     *
     * @var bool $_isGateway
     */
    protected $_isGateway = false;
    /**
     * Payment Method feature
     *
     * @var bool $_isOffline
     */
    protected $_isOffline = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canOrder
     */
    protected $_canOrder = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canAuthorize
     */
    protected $_canAuthorize = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canCapture
     */
    protected $_canCapture = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canCapturePartial
     */
    protected $_canCapturePartial = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canCaptureOnce
     */
    protected $_canCaptureOnce = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canRefund
     */
    protected $_canRefund = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canRefundInvoicePartial
     */
    protected $_canRefundInvoicePartial = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canVoid
     */
    protected $_canVoid = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canUseInternal
     */
    protected $_canUseInternal = true;
    /**
     * Payment Method feature
     *
     * @var bool $_canUseCheckout
     */
    protected $_canUseCheckout = true;
    /**
     * Payment Method feature
     *
     * @var bool $_isInitializeNeeded
     */
    protected $_isInitializeNeeded = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canFetchTransactionInfo
     */
    protected $_canFetchTransactionInfo = false;
    /**
     * Payment Method feature
     *
     * @var bool $_canReviewPayment
     */
    protected $_canReviewPayment = false;
    /**
     * TODO: whether a captured transaction may be voided by this gateway
     * This may happen when amount is captured, but not settled
     *
     * @var bool $_canCancelInvoice
     */
    protected $_canCancelInvoice = false;
    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array $_debugReplacePrivateDataKeys
     */
    protected $_debugReplacePrivateDataKeys = [];
    /**
     * Payment data
     *
     * @var Data $_paymentData
     */
    protected $_paymentData;
    /**
     * Core store config
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    protected $logger;
    /**
     * $directory field
     *
     * @var DirectoryHelper $directory
     */
    private $directory;
    /**
     * $data field
     *
     * @var array $data
     */
    private $data;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;

    /**
     * @param Config                     $config
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param DirectoryHelper            $directory
     * @param DataObjectFactory          $dataObjectFactory
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Config $config,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        DirectoryHelper $directory,
        DataObjectFactory $dataObjectFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
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
        $this->config            = $config;
        $this->_paymentData      = $paymentData;
        $this->scopeConfig       = $scopeConfig;
        $this->logger            = $logger;
        $this->directory         = $directory;
        $this->data              = $data;
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
        return $this->_canCapturePartial;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canCaptureOnce(): bool
    {
        return $this->_canCaptureOnce;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function canRefundPartialPerInvoice(): bool
    {
        return $this->_canRefundInvoicePartial;
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
        return $this->_canUseInternal;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function canUseCheckout(): bool
    {
        return $this->_canUseCheckout;
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
        return $this->_canFetchTransactionInfo;
    }

    /**
     * {@inheritDoc}
     *
     * @param InfoInterface $payment
     * @param string        $transactionId
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
        return $this->_isGateway;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function isOffline(): bool
    {
        return $this->_isOffline;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     * @api
     */
    public function isInitializeNeeded(): bool
    {
        return $this->_isInitializeNeeded;
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
            $this->_formBlockType = $this->data['formBlockType'];
        }

        return $this->_formBlockType;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     * @api
     */
    public function getInfoBlockType(): string
    {
        return $this->_infoBlockType;
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
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param string                $field
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
        if (empty($this->_code)) {
            throw new LocalizedException(
                __('We cannot retrieve the payment method code.')
            );
        }

        return $this->_code;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float                    $amount
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
        return $this->_canOrder;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float                    $amount
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
        return $this->_canAuthorize;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float                    $amount
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
        return $this->_canCapture;
    }

    /**
     * {@inheritDoc}
     *
     * @param DataObject|InfoInterface $payment
     * @param float|string             $amount
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
        return $this->_canRefund;
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
        return $this->_canVoid;
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
        return $this->_canReviewPayment;
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
            AbstractDataAssignObserver::MODEL_CODE  => $this->getInfoInstance(),
            AbstractDataAssignObserver::DATA_CODE   => $data,
        ]);

        $this->_eventManager->dispatch('payment_method_assign_data', [
            AbstractDataAssignObserver::METHOD_CODE => $this,
            AbstractDataAssignObserver::MODEL_CODE  => $this->getInfoInstance(),
            AbstractDataAssignObserver::DATA_CODE   => $data,
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
    public function isAvailable(CartInterface $quote = null): bool
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = $this->dataObjectFactory->create();
        $checkResult->setData('is_available', true);

        // for future use in observers
        $this->_eventManager->dispatch('payment_method_is_active', [
            'result'          => $checkResult,
            'method_instance' => $this,
            'quote'           => $quote,
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
     * @return mixed[]
     */
    public function getDebugReplacePrivateDataKeys(): array
    {
        return (array)$this->_debugReplacePrivateDataKeys;
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
     * @param string[]  $data
     * @param bool|null $isApiOrder
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSuccessUrl(array $data, bool $isApiOrder = null): string
    {
        if (isset($data['successUrl']) && !$isApiOrder) {
            return $data['successUrl'];
        }

        return $this->config->getStoreUrl() . 'checkout_com/payment/verify';
    }

    /**
     * Get the failure redirection URL for the payment request
     *
     * @param string[]  $data
     * @param bool|null $isApiOrder
     *
     * @return string
     */
    public function getFailureUrl(array $data, bool $isApiOrder = null): string
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
        return (bool)$this->scopeConfig->getValue('settings/checkoutcom_configuration/active');
    }
}
