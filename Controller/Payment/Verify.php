<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Verify extends \Magento\Framework\App\Action\Action {
    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var Utilities
     */
    protected $utilities;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    )
    {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->utilities = $utilities;
    
        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Todo - make the method detection generic for 3ds card payments and APMs
        $this->methodId = 'checkoutcom_card_payment';
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        try {
            // Get the session id
            $sessionId = $this->getRequest()->getParam('cko-session-id', null);
            if ($sessionId) {
                // Get the payment details
                $response = $this->apiHandler->getPaymentDetails($sessionId);

                // Process the respoonse
                if ($this->apiHandler->isValidResponse($response)) {

                    if (!$this->placeOrder((array) $response)) {
                        // Todo - Handle the refund as in placeOrder if order creation fails
                    }

                    return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                }
            }
        } catch (\Exception $e) {
            // Add and error message
            $this->messageManager->addErrorMessage(__('The transaction could not be processed or has been cancelled.'));  

            // Return to the cart
            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }        
    }

    /**
     * Handles the order placing process.
     *
     * @param      array    $response  The response
     *
     * @return     mixed
     */
    protected function placeOrder(array $response = null) {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($this->quote);

            // Create an order
            $order = $this->orderHandler
                ->setMethodId($this->methodId)
                ->handleOrder($reservedIncrementId);

            // Add the payment info to the order
            $order = $this->utilities
                ->setPaymentData($order, $response);

            return $order;
        }
        catch(\Exception $e) {
            return false;
        }
    }
}