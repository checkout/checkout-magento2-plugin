<?php

namespace CheckoutCom\Magento2\Controller\Api;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception as WebException;
use Magento\Framework\Webapi\Rest\Response as WebResponse;

class v1 extends \Magento\Framework\App\Action\Action {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

	/**
     * Callback constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    )
    {
        parent::__construct($context);
        $this->config = $config;
        $this->logger = $logger;

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


                // Set a valid response
                $resultFactory->setHttpResponseCode(WebResponse::HTTP_OK);
            }
            else  {
                $resultFactory->setHttpResponseCode(WebException::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            $resultFactory->setHttpResponseCode(WebException::HTTP_INTERNAL_ERROR);
            $this->logger->write($e->getMessage());
            return $resultFactory->setData(['error_message' => $e->getMessage()]);
        } 
    }

    protected function getPayload() {
        return json_decode($this->getRequest()->getContent());
    }
}