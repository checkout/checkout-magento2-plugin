<?php

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\SdkHandlerService;
use \Checkout\Library\HttpHandler;
use \CheckoutCom\Magento2\Model\Methods\CheckoutComConfigMethod;
use CheckoutCom\Magento2\Model\Methods\CardPaymentMethod;



class PlaceOrder extends Action {

	protected $jsonFactory;
    protected $config;
    protected $orderHandler;
    protected $sdk;

	/**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        OrderHandlerService $orderHandler,
        SdkHandlerService $sdk,
        Config $config)
    {

        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->orderHandler = $orderHandler;
        $this->sdk = $sdk;
        $this->config = $config;

    }

    /**
     * Handles the controller method.
     */
    public function execute() {

    	$request = json_decode($this->getRequest()->getContent(), true);
    	$response = array();

        if($this->isValid($request)) {


            switch ($request['source']['type']) {

                case 'token':
                    // Card tokenized payment
                    $this->placeTokenPayment($request);
                    break;

                default:
                    // Alternative payment

                    $this->placeAlternativePayment($request);
                    break;

            }



        }


    	return $this->jsonFactory->create()->setData($request);

    }


    /**
     * Define what is a valid request.
     *
     * @param      array   $body   The body
     *
     * @return     boolean
     */
    protected function isValid($request = array()) {

    	return true;

    }


    /**
     * Payment related functions.
     */

    /**
     * Handle token payments.
     *
     * @param      array   $body   The body
     *
     * @return     boolean
     */
    protected function placeTokenPayment($request = array()) {

        $payment = CardPaymentMethod::createPayment($request);

        return true;

    }

    /**
     * Handle alternative payments.
     *
     * @param      array   $body   The body
     *
     * @return     boolean
     */
    protected function placeAlternativePayment($request = array()) {

        $payment = CardPaymentMethod::createPayment($request);

        return true;

    }


}
