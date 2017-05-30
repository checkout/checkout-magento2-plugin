<?php

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\OrderService;

class PlaceOrder extends AbstractAction {

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param Session $session
     * @param GatewayConfig $gatewayConfig
     * @param OrderService $orderService
     */
    public function __construct(Context $context, Session $session, GatewayConfig $gatewayConfig, OrderService $orderService) {
        parent::__construct($context, $gatewayConfig);

        $this->session          = $session;
        $this->orderService     = $orderService;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {
        $resultRedirect = $this->getResultRedirect();
        $cardToken      = $this->getRequest()->getParam('cko-card-token');
        $email          = $this->getRequest()->getParam('cko-context-id');
        $agreement      = array_keys($this->getRequest()->getPostValue('agreement', []));
        $quote          = $this->session->getQuote();

        if( is_string($email) ) {
            $this->assignGuestEmail($quote, $email);
        }

        try {
            $this->validateQuote($quote);
            $this->orderService->execute($quote, $cardToken, $agreement);

            return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
            
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }

}
