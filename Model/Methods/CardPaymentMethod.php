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
    const FIELDS = array('title', 'environment', 'public_key', 'type', 'action', '3ds_enabled', 'attempt_n3d', 'save_cards_enabled', 'save_cards_title', 'dynamic_decriptor_enabled', 'decriptor_name', 'decriptor_city', 'cvv_optional', 'mada_enabled', 'active');

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

	/**
     * Send a charge request.
     */
    public function sendPaymentRequest($cardToken, $amount, $currency, $reference = '') {
        try {
            // Set the token source
            $tokenSource = new TokenSource($cardToken);

            // Set the payment
            $request = new Payment(
                $tokenSource, 
                $currency
            );

            // Prepare the capture date setting
            $captureDate = $this->config->getCaptureDate($this->_code);

            // Prepare the MADA setting
            $madaEnabled = (bool) $this->config->getValue('mada_enabled', $this->_code);

            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($this->_code);
            $request->amount = $amount*100;
            $request->reference = $reference;
            $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/success';
            $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/failure';
            $request->attempt_n3d = (bool) $this->config->getValue('attempt_n3d', $this->_code);
            $request->threeDs = new ThreeDs($this->config->needs3ds($this->_code));
            $request->description = __('Payment request from %1', $this->config->getStoreName());
            if ($captureDate) {
                $request->capture_on = $this->config->getCaptureDate($this->_code);
            }
            // Todo - add the card BIN check
            if ($madaEnabled) {
                $request->metadata = ['udf1' => 'MADA'];
            }

            // Send the charge request
            $response = $this->apiHandler->checkoutApi
                ->payments()
                ->request($request);

            // Todo - remove logging code
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/card_response.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($response, 1));

            return $response;
        }   

        catch(\Exception $e) {
            // Todo - remove logging code
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/card_error.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($e->getMessage(), 1));
        }
    }

}
