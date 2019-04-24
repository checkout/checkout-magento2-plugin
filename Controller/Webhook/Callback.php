<?php

namespace CheckoutCom\Magento2\Controller\Webhook;

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
        // Get the post data
        $postData = $this->getRequest()->getPostValue();


        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/webhook.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info(print_r($postData, 1));
    }
}