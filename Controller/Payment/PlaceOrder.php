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

    	$post = json_decode($this->getRequest()->getContent(), true);
    	$response = array();

    	// if($post && $this->validateRequest($post)) {

// @todo: this

    	// }

    	return $this->jsonFactory->create()->setData($post);

    }


    /**
     * Define what is a valid request.
     *
     * @param      array   $body   The body
     *
     * @return     boolean
     */
    protected function validateRequest($body) {

    	return true;

    }

}
