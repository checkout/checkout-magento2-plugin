<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class PlaceOrder extends \Magento\Framework\App\Action\Action {
    
    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

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
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $ordereHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
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
            try {
                // Prepare the parameters
                $success = false;
                $message = '';
                $methodId = $this->getRequest()->getParam('methodId');
                $cardToken = $this->getRequest()->getParam('cardToken');
            
                // Get the quote
                $quote = $this->quoteHandler->getQuote();
                if ($quote) {
                    // Send the charge request
                    $success = $this->apiHandler
                        ->sendChargeRequest(
                            $methodId,
                            $cardToken, 
                            $quote->getGrandTotal(),
                            $quote->getQuoteCurrencyCode()
                        )
                        ->processResponse();

                    // Handle the order
                    if ($success) {
                        $this->orderHandler->placeOrder(
                            $methodId,
                            $quote->reserveOrderId()->save()->getReservedOrderId()
                        );
                    }
                    else {
                        $message = __('The transaction could not be processed.');
                    }
                }
            }
            catch(\Exception $e) {
                $message = new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }   
        }
        else {
            $message = new \Magento\Framework\Exception\LocalizedException(
                __('Invalid request.')
            );
        }

        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'message' => $message
        ]);
    }
}
