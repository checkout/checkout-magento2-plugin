<?php

namespace CheckoutCom\Magento2\Block\Adminhtml\Order\View;

use Checkout\CheckoutApi;
use Checkout\Library\HttpHandler;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Checkoutcom\Magento2\Helper\Utilities;
use Magento\Framework\ObjectManagerInterface;
use Checkout\Models\Payments\Payment;
use CheckoutCom\Magento2\Model\Api\V3;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Api\Data\CartInterface;
use CheckoutCom\Magento2\Helper\Logger;

class View extends \Magento\Backend\Block\Template
{
    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var V3
     */
    private $api;

    /**
     * @var ApiHandlerService
     */
    private $apiHandler;

    /**
     * $checkoutApi field
     *
     * @var CheckoutApi $checkoutApi
     */
    protected $checkoutApi;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Utilities $utilities
     * @param ObjectManagerInterface $objectManager
     * @param V3 $api
     * @param ApiHandlerService $apiHandler
     * @param CheckoutApi $checkoutApi
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param CartInterface $cart
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Utilities $utilities,
        ObjectManagerInterface $objectManager,
        V3 $api,
        ApiHandlerService $apiHandler,
        CheckoutApi $checkoutApi,
        StoreManagerInterface $storeManager,
        Http $request,
        CartInterface $cart,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->utilities = $utilities;
        $this->_objectManager = $objectManager;
        $this->api = $api;
        $this->apiHandler = $apiHandler;
        $this->checkoutApi = $checkoutApi;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->cart = $cart;
        $this->logger = $logger;
    }

    // A modifier pour récupérer les info plus simplement. Les info sont désormais passés dans le Place order

    public function getPaymentId()
    {
        $paymentId = $this->getOrder()->getPayment()->getId();
        return $paymentId;
    }

    public function getQuote()
    {
        $quoteId = $this->getOrder()->getQuoteId();
        $quote = $this->cart->load($quoteId);
        return $quote;

    }

    public function getCardInformation($paymentId)
    {

        // Get store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $rep = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        //$checkoutApi = $this->apiHandler->getCheckoutApi();

        // Get the payment details
        //$test = $this->apiHandler->getPaymentDetails($paymentId);

        $validResponse = $this->apiHandler->isValidResponse($test);



        return $response;
    }

    public function sectionName()
    {
        return "Payment Additional Information";
    }

    public function getOrder(): OrderInterface
    {
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($this->getRequest()->getParam('order_id'));
        return $order;
    }

    public function getCardType(OrderInterface $order): ?array
    {
        // get card type from order
        $paymentData = $order->getPayment()
            ->getMethodInstance()
            ->getInfoInstance()
            ->getData();
            //get source


        $paymentData = $this->utilities->objectToArray($paymentData);
        $paymentData = $paymentData['additional_information'];
        var_dump($paymentData);
        return $paymentData;
    }

    public function getPaymentData(OrderInterface $order): ?array
    {
        return $this->utilities->getPaymentData($order);
    }



    public function getCardExpiryMonth(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order);
        if (isset($paymentData['cc_exp_month'])) {
            return $paymentData['cc_exp_month'];
        }
        return null;
    }

    public function getCardExpiryYear(OrderInterface $order): ?string
    {

        $paymentData = $this->getPaymentData($order);
        $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();
        var_dump($paymentInfo['id']);

        return $paymentData['id'];
    }



}
