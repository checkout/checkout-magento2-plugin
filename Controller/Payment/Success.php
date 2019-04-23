<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Success extends \Magento\Framework\App\Action\Action {
    
	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
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
