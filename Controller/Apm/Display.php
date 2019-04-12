<?php

namespace CheckoutCom\Magento2\Controller\Apm;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Gateway\Config\Config;

class PlaceOrder extends Action {

	protected $jsonFactory;
    protected $config;

	/**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Config $config
    )
    {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {

 
    }

}
