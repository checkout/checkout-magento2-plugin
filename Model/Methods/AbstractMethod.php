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

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
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
     * @var ScopeConfigInterface $_scopeConfig
     */
    protected $_scopeConfig;
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
     * AbstractMethod constructor
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     * @param DirectoryHelper|null       $directory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory
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
        $this->_paymentData = $paymentData;
        $this->_scopeConfig = $scopeConfig;
        $this->logger       = $logger;
        $this->directory    = $directory;
        $this->data         = $data;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $storeId
     *
     * @return void
     */
    public function setStore($storeId)
    {
        $this->setData('store', (int)$storeId);
    }

    /**
     * Check partial capture availability
     *
     * @return bool
     * @api
     */
    public function canCapturePartial()
    {
        return $this->_canCapturePartial;
    }

    /**
     * Check whether capture can be performed once and no further capture possible
     *
     * @return bool
     * @api
     */
    public function canCaptureOnce()
    {
        return $this->_canCaptureOnce;
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     * @api
     */
    public function canRefundPartialPerInvoice()
    {
        return $this->_canRefundInvoicePartial;
    }

    /**
     * Using internal pages for input payment data.
     *
     * Can be used in admin.
     *
     * @return bool
     */
    public function canUseInternal()
    {
        return $this->_canUseInternal;
    }

    /**
     * Can be used in regular checkout
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        return $this->_canUseCheckout;
    }

    /**
     * Can be edit order (renew order)
     *
     * @return bool
     * @api
     */
    public function canEdit()
    {
        return true;
    }

    /**
     * Check fetch transaction info availability
     *
     * @return bool
     * @api
     */
    public function canFetchTransactionInfo()
    {
        return $this->_canFetchTransactionInfo;
    }

    /**
     * Fetch transaction info
     *
     * @param InfoInterface $payment
     * @param string        $transactionId
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @api
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return [];
    }

    /**
     * Retrieve payment system relation flag
     *
     * @return bool
     * @api
     */
    public function isGateway()
    {
        return $this->_isGateway;
    }

    /**
     * Retrieve payment method online/offline flag
     *
     * @return bool
     * @api
     */
    public function isOffline()
    {
        return $this->_isOffline;
    }

    /**
     * Flag if we need to run payment initialize while order place
     *
     * @return bool
     * @api
     */
    public function isInitializeNeeded()
    {
        return $this->_isInitializeNeeded;
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * Retrieve block type for method form generation
     *
     * @return string
     */
    public function getFormBlockType()
    {
        if (!empty($this->data['formBlockType'])) {
            $this->_formBlockType = $this->data['formBlockType'];
        }

        return $this->_formBlockType;
    }

    /**
     * Retrieve block type for display method information
     *
     * @return string
     * @api
     */
    public function getInfoBlockType()
    {
        return $this->_infoBlockType;
    }

    /**
     * Retrieve payment information model object
     *
     * @param InfoInterface $info
     *
     * @return void
     * @api
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->setData('info_instance', $info);
    }

    /**
     * Validate payment method information object
     *
     * @return $this
     * @throws LocalizedException
     * @api
     */
    public function validate()
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
     * Retrieve payment information model object
     *
     * @return InfoInterface
     * @throws LocalizedException
     * @api
     */
    public function getInfoInstance()
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
     * To check billing country is allowed for the payment method
     *
     * @param string $country
     *
     * @return bool
     * @throws LocalizedException
     */
    public function canUseForCountry($country)
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
     * Retrieve information from payment configuration
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

        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
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
     * Retrieve payment method code
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCode()
    {
        if (empty($this->_code)) {
            throw new LocalizedException(
                __('We cannot retrieve the payment method code.')
            );
        }

        return $this->_code;
    }

    /**
     * Order payment abstract method
     *
     * @param DataObject|InfoInterface $payment
     * @param float                    $amount
     *
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function order(InfoInterface $payment, $amount)
    {
        if (!$this->canOrder()) {
            throw new LocalizedException(__('The order action is not available.'));
        }

        return $this;
    }

    /**
     * Check order availability
     *
     * @return bool
     * @api
     */
    public function canOrder()
    {
        return $this->_canOrder;
    }

    /**
     * Authorize payment abstract method
     *
     * @param DataObject|InfoInterface $payment
     * @param float                    $amount
     *
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        }

        return $this;
    }

    /**
     * Check authorize availability
     *
     * @return bool
     * @api
     */
    public function canAuthorize()
    {
        return $this->_canAuthorize;
    }

    /**
     * Capture payment abstract method
     *
     * @param DataObject|InfoInterface $payment
     * @param float                    $amount
     *
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }

        return $this;
    }

    /**
     * Check capture availability
     *
     * @return bool
     * @api
     */
    public function canCapture()
    {
        return $this->_canCapture;
    }

    /**
     * Refund specified amount for payment
     *
     * @param DataObject|InfoInterface $payment
     * @param float                    $amount
     *
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        }

        return $this;
    }

    /**
     * Check refund availability
     *
     * @return bool
     * @api
     */
    public function canRefund()
    {
        return $this->_canRefund;
    }

    /**
     * Cancel payment abstract method
     *
     * @param DataObject|InfoInterface $payment
     *
     * @return $this
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cancel(InfoInterface $payment)
    {
        return $this;
    }

    /**
     * Void payment abstract method
     *
     * @param DataObject|InfoInterface $payment
     *
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function void(InfoInterface $payment)
    {
        if (!$this->canVoid()) {
            throw new LocalizedException(__('The void action is not available.'));
        }

        return $this;
    }

    /**
     * Check void availability.
     *
     * @return bool
     * @internal param \Magento\Framework\DataObject $payment
     * @api
     */
    public function canVoid()
    {
        return $this->_canVoid;
    }

    /**
     * Attempt to accept a payment that us under review
     *
     * @param InfoInterface $payment
     *
     * @return false
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function acceptPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
    }

    /**
     * Whether this method can accept or deny payment.
     *
     * @return bool
     * @api
     */
    public function canReviewPayment()
    {
        return $this->_canReviewPayment;
    }

    /**
     * Attempt to deny a payment that us under review
     *
     * @param InfoInterface $payment
     *
     * @return false
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function denyPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     * @throws LocalizedException
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * Assign data to info model instance
     *
     * @param array|DataObject $data
     *
     * @return $this
     * @throws LocalizedException
     * @api
     */
    public function assignData(DataObject $data)
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
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
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
     * Is active
     *
     * @param int|null $storeId
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }

    /**
     * Method that will be executed instead of authorize or capture if flag isInitializeNeeded set to true.
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @api
     */
    public function initialize($paymentAction, $stateObject)
    {
        return $this;
    }

    /**
     * Get config payment action url.
     *
     * Used to universalize payment actions when processing payment place.
     *
     * @return string
     * @throws LocalizedException
     * @api
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('payment_action');
    }

    /**
     * Used to call debug method from not Payment Method context
     *
     * @param mixed $debugData
     *
     * @return void
     * @throws LocalizedException
     * @api
     */
    public function debugData($debugData)
    {
        $this->_debug($debugData);
    }

    /**
     * Log debug data to file
     *
     * @param array $debugData
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _debug($debugData)
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
    public function getDebugReplacePrivateDataKeys()
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
    public function getDebugFlag()
    {
        return (bool)(int)$this->getConfigData('debug');
    }

    /**
     * Get the success redirection URL for the payment request
     *
     * @param      $data
     * @param null $isApiOrder
     *
     * @return string
     */
    public function getSuccessUrl($data, $isApiOrder = null)
    {
        if (isset($data['successUrl']) && !$isApiOrder) {
            return $data['successUrl'];
        }

        return $this->config->getStoreUrl() . 'checkout_com/payment/verify';
    }

    /**
     * Get the failure redirection URL for the payment request
     *
     * @param      $data
     * @param null $isApiOrder
     *
     * @return string
     */
    public function getFailureUrl($data, $isApiOrder = null)
    {
        if (isset($data['failureUrl']) && !$isApiOrder) {
            return $data['failureUrl'];
        }

        return $this->config->getStoreUrl() . 'checkout_com/payment/fail';
    }

    /**
     * Initializes injected data
     *
     * @param array $data
     *
     * @return void
     */
    protected function initializeData($data = [])
    {
        if (!empty($data['formBlockType'])) {
            $this->_formBlockType = $data['formBlockType'];
        }
    }
}
