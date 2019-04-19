<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class PlaceOrder extends \Magento\Framework\App\Action\Action {
    
    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * @var JsonFactory
     */
     protected $jsonFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $config;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->orderHandler = $orderHandler;
        $this->apiHandler = $apiHandler;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if ($this->getRequest()->isAjax()) {
            // Get the request parameters
            $methodId = $this->getRequest()->getParam('methodId');
            $cardToken = $this->getRequest()->getParam('cardToken');
        }

        // Send the charge request
        $order = $this->orderHandler->getOrder([
            'entity_id' => 2
        ]);

        if ($order) {
            var_dump($order->getIncrementId());
        }

        $this->apiHandler->sendChargeRequest($methodId, $cardToken, $order);

        // Return the result
    	return $this->jsonFactory->create()->setData([
            'success' => false,
            'message' => __('There  was  an error with the transaction.')
        ]);
    }
}
