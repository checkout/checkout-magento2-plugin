<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\OrderService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use CheckoutCom\Magento2\Model\Service\VerifyPaymentService;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Checkout\Model\Cart;
use CheckoutCom\Magento2\Helper\Watchdog;

class Fail extends AbstractAction {

    /**
     * @var ResultRedirect 
     */
    protected $redirect;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var VerifyPaymentService
     */

    /**
     * @var Watchdog
     */
    protected $watchdog;

    protected $verifyPaymentService;

    protected $paymentTokenManagement;

    protected $customerSession;

    protected $response;

    protected $orderInterface;

    protected $cart;

    /**
     * Fail constructor.
     * @param Context $context
     * @param Session $session
     * @param VerifyPaymentService $verifyPaymentService
     * @param GatewayConfig $gatewayConfig
     * @param Watchdog $watchdog
     */
    public function __construct(Context $context, Session $session, GatewayConfig $gatewayConfig, QuoteManagement $quoteManagement, OrderSender $orderSender, PaymentTokenManagementInterface $paymentTokenManagement, CustomerSession $customerSession, VerifyPaymentService $verifyPaymentService, OrderInterface $orderInterface, Cart $cart, Watchdog $watchdog) {
        parent::__construct($context, $gatewayConfig);
        $this->quoteManagement      = $quoteManagement;
        $this->orderSender          = $orderSender;
        $this->session          = $session;
        $this->redirect = $this->getResultRedirect();
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSession;
        $this->verifyPaymentService = $verifyPaymentService;
        $this->orderInterface = $orderInterface;
        $this->cart = $cart;
        $this->watchdog = $watchdog;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {

        // Get the payment token from response
        $paymentToken = $this->extractPaymentToken();

        // Process the gateway response
        $this->response = $this->verifyPaymentService->verifyPayment($paymentToken);

        // Debug info
        $this->watchdog->bark($this->response);

        // If this is a 3D Secure fail
        if ($this->is3DSFailure()) {

            // Try to rebuild the customer cart
            $this->rebuildFailedOrderCart();
        }

        // Add the response message
        $this->messageManager->addErrorMessage( __("The transaction couldn't be processed or has been cancelled."));                

        // Redirect to cart
        return $this->redirect->setPath('checkout/cart', ['_secure' => true]);
    }

    public function rebuildFailedOrderCart() {

        // Load the order
        $order = $this->orderInterface->loadByIncrementId($this->response['trackId']);

        // Add the items
        $items = $order->getItemsCollection();
         
        foreach ($items as $item) {
         
            try {
                $this->cart->addOrderItem($item);
            }
            catch (\Exception $e) {
                $this->session->addException($e, __('Cannot add the item to shopping cart.'));
            }
         
        }
        
        // Save the current cart
        $this->cart->save();

        // Cancel the previous order
        $order->cancel()->save();
    }

    public function is3DSFailure() {
        return isset($this->response['trackId']) 
        && isset($this->response['chargeMode']) 
        && isset($this->response['value']) 
        && (int) $this->response['chargeMode'] == 2 
        && (int) $this->response['value'] > 0 
        && (int) $this->response['responseCode'] != 10000 
        && (int) $this->response['responseCode'] != 10100;
    }

    public function extractPaymentToken() {

        // Get the gateway response from session if exists
        $gatewayResponseId = $this->session->getGatewayResponseId();

        // Destroy the session variable
        $this->session->unsGatewayResponseId();

        // Check if there is a payment token sent in url
        $ckoPaymentToken = $this->getRequest()->getParam('cko-payment-token');

        // return the found payment token
        return $ckoPaymentToken ? $ckoPaymentToken : $gatewayResponseId;
    }    
}
