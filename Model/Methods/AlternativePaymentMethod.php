<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\AlipaySource;
use \Checkout\Models\Payments\BoletoSource;
use \Checkout\Models\Payments\GiropaySource;
use \Checkout\Models\Payments\IdealSource;
use \Checkout\Models\Payments\PoliSource;
use \Checkout\Models\Payments\SofortSource;

class AlternativePaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_apm';

    /**
     * @var string
     */
    const FIELD_ALTERNATIVES = 'apm';

    /**
     * @var string
     */
    const FIELD_ACTIVE = 'active';

    /**
     * @var string
     */
    const FIELD_TITLE = 'title';

    /**
     * @var array
     */
    const FIELDS = array(AlternativePaymentMethod::FIELD_TITLE,
                        AlternativePaymentMethod::FIELD_ACTIVE,
                        AlternativePaymentMethod::FIELD_ALTERNATIVES);

    /**
     * @var string
     */
    const PAYMENT_SEPA = 'sepa';

    /**
     * @var string
     */
    const PAYMENT_ALIPAY = 'alipay';

    /**
     * @var string
     */
    const PAYMENT_BOLETO = 'boleto';

    /**
     * @var string
     */
    const PAYMENT_GIROPAY = 'giropay';

    /**
     * @var string
     */
    const PAYMENT_IDEAL = 'ideal';

    /**
     * @var string
     */
    const PAYMENT_POLI = 'poli';

    /**
     * @var string
     */
    const PAYMENT_QIWI = 'qiwi';

    /**
     * @var string
     */
    const PAYMENT_SAFETYPAY = 'safetypay';

    /**
     * @var string
     */
    const PAYMENT_KLARNA = 'klarna';

    /**
     * @var string
     */
    const PAYMENT_SOFORT = 'sofort';

    /**
     * @var array
     */
    const PAYMENT_LIST = array(
        AlternativePaymentMethod::PAYMENT_SEPA => 'SEPA',
        AlternativePaymentMethod::PAYMENT_ALIPAY => 'Alipay',
        AlternativePaymentMethod::PAYMENT_BOLETO => 'Boleto',
        AlternativePaymentMethod::PAYMENT_GIROPAY => 'Giropay',
        AlternativePaymentMethod::PAYMENT_IDEAL => 'iDEAL',
        AlternativePaymentMethod::PAYMENT_POLI => 'Poli',
        //AlternativePaymentMethod::PAYMENT_QIWI => 'Qiwi',
        //AlternativePaymentMethod::PAYMENT_SAFETYPAY => 'SafetyPay',
        AlternativePaymentMethod::PAYMENT_KLARNA => 'Klarna',
        AlternativePaymentMethod::PAYMENT_SOFORT => 'Sofort'
    );

    /**
     * @var array
     */
    const PAYMENT_FIELDS = array(
        AlternativePaymentMethod::PAYMENT_SEPA => AlternativePaymentMethod::PAYMENT_FIELDS_SEPA,
        AlternativePaymentMethod::PAYMENT_ALIPAY => AlternativePaymentMethod::PAYMENT_FIELDS_ALIPAY,
        AlternativePaymentMethod::PAYMENT_BOLETO => AlternativePaymentMethod::PAYMENT_FIELDS_BOLETO,
        AlternativePaymentMethod::PAYMENT_GIROPAY => AlternativePaymentMethod::PAYMENT_FIELDS_GIROPAY,
        AlternativePaymentMethod::PAYMENT_IDEAL => AlternativePaymentMethod::PAYMENT_FIELDS_IDEAL,
        AlternativePaymentMethod::PAYMENT_POLI => AlternativePaymentMethod::PAYMENT_FIELDS_POLI,
        //AlternativePaymentMethod::PAYMENT_QIWI => AlternativePaymentMethod::PAYMENT_FIELDS_QIWI,
        //AlternativePaymentMethod::PAYMENT_SAFETYPAY => AlternativePaymentMethod::PAYMENT_FIELDS_SAFETYPAY,
        AlternativePaymentMethod::PAYMENT_KLARNA => AlternativePaymentMethod::PAYMENT_FIELDS_KLARNA,
        AlternativePaymentMethod::PAYMENT_SOFORT => AlternativePaymentMethod::PAYMENT_FIELDS_SOFORT
    );

    /**
     * Required fields.
     */

    /**
     * @var array
     */
    const PAYMENT_FIELDS_SEPA = array('first_name', 'last_name', 'account_iban', 'billing_descriptor', 'mandate_type');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_ALIPAY = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_BOLETO = array('customerName', 'birthDate', 'cpf');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_GIROPAY = array('purpose', 'bic', 'iban');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_IDEAL = array('bic', 'description');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_POLI = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_QIWI = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_SAFETYPAY = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_KLARNA = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_SOFORT = array();

    /**
     * @var string
     * @overriden
     */
    protected $_code = AlternativePaymentMethod::CODE;

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
    }

}
