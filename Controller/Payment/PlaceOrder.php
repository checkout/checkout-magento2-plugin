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
use CheckoutCom\Magento2\Model\Methods\AlternativePaymentMethod;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Customer\Model\Session as CustomerSession;
use \Magento\Quote\Model\QuoteFactory;


class PlaceOrder extends Action {

	protected $jsonFactory;
    protected $config;
    protected $orderHandler;
    protected $sdk;
    protected $quoteFactory;
    protected $checkoutSession;
    protected $customerSession;

	/**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        OrderHandlerService $orderHandler,
        SdkHandlerService $sdk,
        QuoteFactory $quoteFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Config $config)
    {

        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->orderHandler = $orderHandler;
        $this->sdk = $sdk;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->config = $config;

    }

    /**
     * Handles the controller method.
     */
    public function execute() {

    	$request = json_decode($this->getRequest()->getContent(), true);
    	$response = array();

        if($this->isValid($request)) {

            $payment = null;
            switch ($request['source']['type']) {

                case 'token':
                    $payment = CardPaymentMethod::createPayment($request, $this->orderHandler->getCurrency());
                    break;

                case 'googlepay':
                    break;

                case 'applepay':
                    break;

                default:

                    // Alternative payment
                    $payment = AlternativePaymentMethod::createPayment($request, $this->orderHandler->getCurrency());
                    break;

            }

            //$this->pay($payment);

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
     * Process payment.
     *
     * @param      Payment   $body   The body
     *
     * @return     boolean
     */
    protected function pay(Payment $payment) {

        /**
         * @todo: set order reference, authorize/capture, shipping address and all that.
         *        handle further action needed.
         */

        return true;

    }

}
