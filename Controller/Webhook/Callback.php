<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Callback extends \Magento\Framework\App\Action\Action {
    
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

	/**
     * Webhook constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    )
    {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        
    }
}
