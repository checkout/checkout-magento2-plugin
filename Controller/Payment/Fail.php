<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Fail extends \Magento\Framework\App\Action\Action {
    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

    /**
     * @var Logger
     */
    protected $logger;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    )
    {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
        $this->logger = $logger;
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
                // Todo - Display the gateway error message from $response if debug mode is on
                $response = $this->apiHandler->getPaymentDetails($sessionId);
                
                // Display the message
                $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));  
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            // Return to the cart
            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }
    }
}