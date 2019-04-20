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
     * @var Bool
     */
    protected $success;

    /**
     * @var String
     */
    protected $message;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * @var String
     */
    protected $cardToken;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
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

        // Set some required properties
        $this->setParameters();
    }

    /**
     * Handles the order placing process.
     */
    protected function placeOrder() {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quote
                ->reserveOrderId()
                ->save()
                ->getReservedOrderId();

            // Create an order
            $order = $this->orderHandler->placeOrder(
                $this->methodId,
                $reservedIncrementId
            );

            // Prepare place order redirection
            return $this->orderHandler->afterPlaceOrder(
                $this->quote,
                $order
            );
        }
        catch(\Exception $e) {
            return false;
        }   
    }

    /**
     * Prepare some required properties.
     */
    protected function setParameters() {
        try {
            $this->success = false;
            $this->message = '';
            $this->methodId = $this->getRequest()->getParam('methodId');
            $this->cardToken = $this->getRequest()->getParam('cardToken');
            $this->quote = $this->quoteHandler->getQuote();
        }
        catch(\Exception $e) {

        }   
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if ($this->getRequest()->isAjax()) {
            try {
                if ($this->quote) {
                    // Send the charge request
                    $this->success = $this->apiHandler
                        ->sendChargeRequest(
                            $this->methodId,
                            $this->cardToken, 
                            $this->quote->getGrandTotal(),
                            $this->quote->getQuoteCurrencyCode()
                        )
                        ->processResponse();

                    // Handle the order
                    if ($this->success && $this->placeOrder()) {
                        $this->message = [
                            'orderId' => $order->getId(),
                            'orderIncrementId' => $order->getIncrementId()
                        ];
                    }
                    else {
                        $this->message = __('The transaction could not be processed.');
                    }
                }
            }
            catch(\Exception $e) {
                $this->message = new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }   
        }
        else {
            $this->message = new \Magento\Framework\Exception\LocalizedException(
                __('Invalid request.')
            );
        }

        return $this->jsonFactory->create()->setData([
            'success' => $this->success,
            'message' => $this->message
        ]);
    }
}
