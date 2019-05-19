<?php

namespace CheckoutCom\Magento2\Model\Methods;

use Magento\Framework\DataObject;
use Magento\Framework\Module\Dir;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;

abstract class Method extends \Magento\Payment\Model\Method\AbstractMethod
{

	protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCancel = true;
    protected $_canCapturePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

	/**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * Magic Methods
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
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
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

        $this->remoteAddress      = $remoteAddress;
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
    }


    /**
     * Methods
     */

    /**
     * Safely get value from a multidimentional array.
     *
     * @param      array  $array  The value
     *
     * @return     Payment
     */
    public static function getValue($field, $array, $dft = null) {

        $value = null;
        $field = (array) $field;

        foreach ($field as $key) {

            if(isset($array[$key])) {
                $value = $array[$key];
                $array = $array[$key];
            } else {
                $value = $dft;
                break;
            }

        }

        return $value;

    }

    /**
     * API related.
     */

    /**
     * Create a payment object based on the body.
     *
     * @param      array  $array  The value
     *
     * @return     Payment
     */
    protected function createPayment(IdSource $source, int $amount, string $currency, string $reference) {

        $payment = null;

        // Create payment object
        $payment = new Payment($source, $currency);

        // Set the payment specifications
        $payment->capture = $this->config->needsAutoCapture($this->_code);
        $payment->amount = $amount * 100;
        $payment->reference = $reference;
        $payment->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $payment->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';

        $payment->description = __('Payment request from %1', $this->config->getStoreName());
        $payment->payment_ip = $this->remoteAddress->getRemoteAddress();

        return $payment;

    }

}
