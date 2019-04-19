<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class PlaceOrder extends \Magento\Framework\App\Action\Action {
    
    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

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
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
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
        
            // Send the charge request
            $quote = $this->quoteHandler->getQuote();

            if ($quote) {
                $this->apiHandler->sendChargeRequest(
                    $methodId,
                    $cardToken, 
                    $quote->getGrandTotal(),
                    $quote->getQuoteCurrencyCode()
                );

                return 'code success';
            }
        }

        // Return the result
    	return $this->jsonFactory->create()->setData([
            'success' => false,
            'message' => __('There  was  an error with the transaction.')
        ]);
    }
}
