<?php

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class PlaceOrder extends Action {

	protected $jsonFactory;

	/**
     * @param Context $context
     */
    public function __construct(Context $context, JsonFactory $jsonFactory)
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;

    }

    /**
     * Handles the controller method.
     */
    public function execute() {

    	return $this->jsonFactory->create()->setData(array('aquila' => 'freitas'));

    }

}
