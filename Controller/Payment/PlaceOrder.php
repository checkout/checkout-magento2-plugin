<?php

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use \Checkout\Library\HttpHandler;
use CheckoutCom\Magento2\Model\Methods\CardPaymentMethod;
use CheckoutCom\Magento2\Model\Methods\AlternativePaymentMethod;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Customer\Model\Session as CustomerSession;
use \Magento\Quote\Model\QuoteFactory;


class PlaceOrder extends Action {

	protected $jsonFactory;
    protected $config;
    protected $orderHandler;
    protected $api;
    protected $quoteFactory;
    protected $checkoutSession;
    protected $customerSession;

	/**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        OrderHandlerService $orderHandler,
        ApiHandlerService $api,
        QuoteFactory $quoteFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Config $config)
    {

        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->orderHandler = $orderHandler;
        $this->api = $api;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->config = $config;

    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if ($this->getRequest()->isAjax()) {
            $methodId = $this->getRequest()->getParam('methodId');
            $cardToken = $this->getRequest()->getParam('cardToken');
            
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($methodId, 1));
            $logger->info(print_r($cardToken, 1));


        }

    	return $this->jsonFactory->create()->setData([
            'success' => false,
            'message' => __('There  was  an error with the transaction.')
        ]);

    }

}
