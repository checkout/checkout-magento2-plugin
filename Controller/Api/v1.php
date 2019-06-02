<?php

namespace CheckoutCom\Magento2\Controller\Api;

class v1 extends \Magento\Framework\App\Action\Action {

    /**
     * @var Config
     */
    protected $config;

	/**
     * Callback constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);
        $this->config = $config;

        // Set the payload data
        $this->payload = $this->getPayload();
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        try {
            // Prepare the response handler
            $resultFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);

            // Process the request
            if ($this->config->isValidAuth()) {
    
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/api.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info(print_r($this->payload, 1));

                // Set a valid response
                $resultFactory->setHttpResponseCode(WebResponse::HTTP_OK);
            }
            else  {
                $resultFactory->setHttpResponseCode(WebException::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            $resultFactory->setHttpResponseCode(WebException::HTTP_INTERNAL_ERROR);
            $resultFactory->setData(['error_message' => $e->getMessage()]);
        }   
        
        return $resultFactory;
    }

    protected function getPayload() {
        return json_decode($this->getRequest()->getContent());
    }

}