<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Fail extends \Magento\Framework\App\Action\Action {
    
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    )
    {
        parent::__construct($context);
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        
    }
}
