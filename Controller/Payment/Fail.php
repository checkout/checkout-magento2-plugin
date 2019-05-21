<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Fail extends \Magento\Framework\App\Action\Action {
    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler
    )
    {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
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
                
                // Todo - Display the gateway error message from $response if debug mode is on
                $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));  
            }
        } catch (\Exception $e) {
            // Todo - Log and error message if debug mode is on
        }      

        // Return to the cart
        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}