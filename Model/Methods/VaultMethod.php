<?php

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;

class VaultMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

	/**
     * @var string
     */
    const CODE = 'checkoutcom_vault';

    /**
     * @var string
     * @overriden
     */
    protected $_code = self::CODE;

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
     * @var apiHandler
     */
    protected $apiHandler;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

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
        \CheckoutCom\Magento2\Model\Service\apiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
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
        $this->vaultHandler       = $vaultHandler;
    }

	/**
     * Send a charge request.
     */
    public function sendPaymentRequest($data, $amount, $currency, $reference = '') {
        try {
            // Find the  card token
            $card = $this->vaultHandler->getCardFromHash($data['publicHash']);

            // Set the token source
            $idSource = new IdSource($card->getGatewayToken());

            // Set the payment
            $request = new Payment(
                $idSource,
                $currency
            );

            // Prepare the capture date setting
            $captureDate = $this->config->getCaptureTime($this->_code);

            // Prepare the MADA setting
            $madaEnabled = (bool) $this->config->getValue('mada_enabled', $this->_code);

            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($this->_code);
            $request->amount = $amount*100;
            $request->reference = $reference;
            $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
            $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
            $request->threeDs = new ThreeDs($this->config->needs3ds($this->_code));
            $request->threeDs->attempt_n3d = (bool) $this->config->getValue('attempt_n3d', $this->_code);
            $request->description = __('Payment request from %1', $this->config->getStoreName());
            $request->payment_ip = $this->remoteAddress->getRemoteAddress();
            if ($captureDate) {
                $request->capture_time = $this->config->getCaptureTime($this->_code);
            }
            // Todo - add the card BIN check
            if ($madaEnabled) {
                $request->metadata = ['udf1' => 'MADA'];
            }

            // Send the charge request
            $response = $this->apiHandler->checkoutApi
                ->payments()
                ->request($request);

            return $response;
        }

        catch(\Exception $e) {

        }
    }

    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        // Check the status
        if (!$this->canVoid()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void action is not available.'));
        }

        // Process the void request
        $response = $this->apiHandler->voidTransaction($payment);
        if (!$response || !$response->isSuccessful()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void request could not be processed.'));
        }

        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Check the status
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        // Process the refund request
        $response = $this->apiHandler->refundTransaction($payment, $amount);
        if (!$response || !$response->isSuccessful()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund request could not be processed.'));
        }

        return $this;
    }
}
