<?php

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;

class CardPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

	/**
     * @var string
     */
    const CODE = 'checkoutcom_card_payment';

    /**
     * @var array
     */
    const FIELDS = array('title', 'environment', 'public_key', 'type', 'action', '3ds_enabled', 'attempt_non3ds', 'save_cards_enabled', 'save_cards_title', 'dynamic_decriptor_enabled', 'decriptor_name', 'decriptor_city', 'cvv_optional', 'mada_enabled', 'active');

    /**
     * @var string
     * @overriden
     */
    protected $_code = CardPaymentMethod::CODE;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandlerService;

    /**
     * @var Payment
     */
    protected $request;

    /**
     * @var Payment
     */
    protected $response;

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
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
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
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
    }

    public function test () {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r('hello', 1));
    }

	/**
     * Send a charge request.
     */
    public function sendChargeRequest($cardToken, $amount, $currency, $reference = '') {
        try {
            // Set the token source
            $tokenSource = new TokenSource($cardToken);

            // Set the payment
            $this->request = new Payment(
                $tokenSource, 
                $currency
            );

            // Set the request parameters
            $this->request->capture = $this->config->isAutoCapture($this->_code);
            $this->request->amount = $amount*100;
            $this->request->reference = $reference;
            /*
            $this->request->threeDs = new ThreeDs(
                $this->config->getValue(
                    'three_ds', $this->_code
                )
            );

            $this->request->description = _(
                'Payment request from %1', $this->config->getStoreName()
            );
            */

            // Auto capture time setting
            $this->request = $this->apiHandler
                ->setCaptureDate(
                    $this->_code,
                    $this->request
                );

            // Send the charge request
            $this->response = $this->apiHandler->checkoutApi
                ->payments()
                ->request($this->request);

            // Todo - remove logging code
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/response.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($this->response, 1));

            return $this;

        }   
        catch(\Exception $e) {
            // Todo - remove logging code
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/error.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($e->getMessage(), 1));
        }
    }

}
