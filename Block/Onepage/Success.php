<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CheckoutCom\Magento2\Block\Onepage;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;

/**
 * One page checkout success page
 *
 * @api
 * @since 100.0.2
 */
class Success extends \Magento\Framework\View\Element\Template
{

    const KNET = 'knet';

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
    
    /**
     * @var CheckoutApi
     */
    public $apiHandler;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->apiHandler = $apiHandler;
        $this->storeManager = $storeManager;
    }

    /**
     * Render additional order information lines and return result html
     *
     * @return string
     */
    public function getAdditionalInfoHtml()
    {
        return $this->_layout->renderElement('order.success.additional.info');
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        $this->addData(
            [
                'is_order_visible' => $this->isVisible($order),
                'view_order_url' => $this->getUrl(
                    'sales/order/view/',
                    ['order_id' => $order->getEntityId()]
                ),
                'print_url' => $this->getUrl(
                    'sales/order/print',
                    ['order_id' => $order->getEntityId()]
                ),
                'can_print_order' => $this->isVisible($order),
                'can_view_order'  => $this->canViewOrder($order),
                'order_id'  => $order->getIncrementId()
            ]
        );
    }

    /**
     * Get the order obj from the order id
     * 
     * @param Order $order
     * @return Order
     */
    public function getOrderById()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($this->getOrderId());

        return $order;
    }

    /**
     * Get order info for knet payment
     * 
     */
    public function getKnetInfo()
    {
        $order = $this->getOrderById();
        $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();
        
        $paymentAdditionalInfo = $paymentInfo->getAdditionalInformation('transaction_info');

        // Get payment id from the order
        $paymentId = $paymentAdditionalInfo['id'];

        try {

            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Get the payment details
            $response = $api->getPaymentDetails($paymentId);

            if ($api->isValidResponse($response) && $response->source['type'] === self::KNET) {

                return [
                    'postData' => $response->source['post_date'] ?? null,
                    'amount' => $response->amount ?? null,
                    'paymentId' => $response->source['knet_payment_id'] ?? null,
                    'transactionId' => $response->source['knet_transaction_id'] ?? null,
                    'authCode' => $response->source['auth_code'] ?? null,
                    'reference' => $response->source['bank_reference'] ?? null,
                    'resultCode' => $response->source['knet_result'] ?? null,
                ];
               
            } else {
                return null;
            }

        } catch (\Checkout\Library\Exceptions\CheckoutHttpException $e) {
            
            return null;
        }
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
            && $this->isVisible($order);
    }

    /**
     * @return string
     * @since 100.2.0
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}