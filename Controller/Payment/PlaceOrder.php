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

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Gateway\Config\Config as Config;
use CheckoutCom\Magento2\Model\Service\OrderService;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Watchdog;

class PlaceOrder extends AbstractAction {

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Array
     */
    protected $params = [];

    /**
     * @var Order
     */
    protected $order = null;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param OrderInterface $orderInterface
     * @param OrderService $orderService
     * @param Order $orderManager
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Config $config,
        OrderService $orderService,
        OrderInterface $orderInterface,
        CustomerSession $customerSession,
        PaymentTokenService $paymentTokenService,
        Watchdog $watchdog
    ) {
        parent::__construct($context, $config);

        $this->checkoutSession        = $checkoutSession;
        $this->customerSession        = $customerSession;
        $this->orderService           = $orderService;
        $this->orderInterface         = $orderInterface;
        $this->paymentTokenService    = $paymentTokenService;
        $this->watchdog               = $watchdog; 

        // Get cko-public-key, cko-card-token, cko-payment-token, cko-context-id
        $this->params = $this->getRequest()->getParams();

        // Add the agreement validation to the array of parameters
        //$this->params['agreement'] = array_keys($this->getRequest()->getPostValue('agreement', []));
        $this->params['agreement'] = array(true); // Temporary workaround for a M2 code T&C checkbox issue not sending data
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        $order = null;
        if (isset($this->customerSession->getData('checkoutSessionData')['orderTrackId'])) {
            $order = $this->orderInterface->loadByIncrementId($this->customerSession->getData('checkoutSessionData')['orderTrackId']);
        }

        if ($order) {
            $this->updateOrder();
        }
        else {
            $this->createOrder();
        }
    }

    /**
     * Creates an order on payment return.
     */
    private function createOrder() {
        // Get the quote
        $quote = $this->checkoutSession->getQuote();      

        // Check for guest email
        if ($quote->getCustomerEmail() === null
            && $this->customerSession->isLoggedIn() === false
            && isset($this->customerSession->getData('checkoutSessionData')['customerEmail'])
            && $this->customerSession->getData('checkoutSessionData')['customerEmail'] === $this->params['cko-context-id']) 
        {
            $quote->setCustomerId(null)
            ->setCustomerEmail($params['cko-context-id'])
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        // Perform quote and order validation
        try {
            // Create an order from the quote
            $this->validateQuote($quote);
            $this->order = $this->orderService->placeOrder($this->params);

            // Send the charge
            $response = $this->sendChargeRequest();

            // 3D Secure redirection if needed
            if($this->config->isVerify3DSecure()) {
                $this->place3DSecureRedirectUrl();
                exit();
            }

            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }

    /**
     * Updates and existing order on payment return.
     */
    private function updateOrder() {
        // Create the charge for order already placed
        $response = $this->sendChargeRequest();

        // Handle the response
        $this->handleResponse($response);

        // Update payment data
        $this->updatePaymentData();
        
        // 3D Secure redirection if needed
        if ($this->config->isVerify3DSecure()) {
            $this->place3DSecureRedirectUrl();
            exit();
        }

        return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
    }

    /**
     * Sends a token charge request to the gateway.
     */
     private function sendChargeRequest() {
        return $this->paymentTokenService->sendChargeRequest(
            $this->params['cko-card-token'],
            $this->order
        );
     }

    /**
     * Handle the charge response.
     */
    private function handleResponse($response) {
        // Debug info
        $this->watchdog->bark($response);

        // Handle response code
        if (isset($response['responseCode']) && ((int) $response['responseCode'] == 10000 || (int) $response['responseCode'] == 10100)) {
            // Prepare 3D Secure redirection with session variable for pre auth order
            if (array_key_exists(self::REDIRECT_URL, $response)) {
                
                // Get the 3DS redirection URL
                $redirectUrl = $response[self::REDIRECT_URL];
                
                // Set 3DS redirection in session for the PlaceOrder controller
                $this->checkoutSession->set3DSRedirect($redirectUrl);

                // Put the response in session for the PlaceOrder controller
                $this->checkoutSession->setGatewayResponseId($response['id']);
            }

            return true;
        }
        else {
            if (isset($response['responseMessage'])) {
                $this->messageManager->addErrorMessage($response['responseMessage']);
            }             
        }

        return false;
    }

    /**
     * Updates the order payment data.
     */
    private function updatePaymentData() {
        // Load payment object
        $payment = $this->order->getPayment();

        // Set the payment method, previously "substitution" for pre auth order creation
        $payment->setMethod(ConfigProvider::CODE); 
        $payment->save();
        $this->order->save();
    }

    /**
     * Listens to a session variable set in Gateway/Response/ThreeDSecureDetailsHandler.php.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function place3DSecureRedirectUrl() {
        echo '<script type="text/javascript">';
        echo 'function waitForElement() {';
        echo 'var redirectUrl = "' . $this->checkoutSession->get3DSRedirect() . '";';
        echo 'if (redirectUrl.length != 0){ window.location.replace(redirectUrl); }';
        echo 'else { setTimeout(waitForElement, 250); }';
        echo '} ';
        echo 'waitForElement();';
        echo '</script>';
    }
}
