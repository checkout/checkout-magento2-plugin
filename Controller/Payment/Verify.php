<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Verify extends \Magento\Framework\App\Action\Action {

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

    /**
     * @var Utilities
     */
    protected $utilities;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    )
    {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Get the session id
        $sessionId = $this->getRequest()->getParam('cko-session-id', null);
        if ($sessionId && $this->isValidRequest()) {
            // Get the payment details
            $response = $this->apiHandler->getPaymentDetails($sessionId);

            // Process the respoonse
            //if ($response && $success = $response->isSuccessful()) {
            var_dump($response->isSuccessful());
            var_dump($response);
        }

        exit();
    }

    protected function isValidRequest() {
        return $this->utilities->isValidAuth();
    }
}
