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

        // Prepare the request parameters
        $this->params = array(
            'cardToken' => $this->getRequest()->getParam('cko-card-token'),
            'email' => $this->getRequest()->getParam('cko-context-id'),
            'agreement' => array_keys($this->getRequest()->getPostValue('agreement', [])),
            'quote' => $this->checkoutSession->getQuote()
        );

        // Set the current order property
        $this->order = null;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if (isset($this->customerSession->getData('checkoutSessionData')['orderTrackId'])) {
            // Load the order
            $this->order = $this->orderInterface->loadByIncrementId($this->customerSession->getData('checkoutSessionData')['orderTrackId']);

            if ($this->order) {
                $this->updateOrder();
            }
            else {
                $this->createOrder();
            }
        }
        else {
            // Add the response message
            $this->messageManager->addErrorMessage( __("The order number is invalid."));                

            // Redirect to cart
            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }
    }

    /**
     * Updates and existing order on payment return.
     */
    private function updateOrder() {
        // Create the charge for order already placed
        $response = $this->paymentTokenService->sendChargeRequest(
            $this->params['cardToken'],
            $this->order
        );

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
     * Creates an order on payment return.
     */
    private function createOrder() {
        // Check for guest email
        if ($this->params['quote']->getCustomerEmail() === null
            && $this->customerSession->isLoggedIn() === false
            && isset($this->customerSession->getData('checkoutSessionData')['customerEmail'])
            && $this->customerSession->getData('checkoutSessionData')['customerEmail'] === $this->params['email']) 
        {
            $this->params['quote']->setCustomerId(null)
            ->setCustomerEmail($params['email'])
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        // Perform quote and order validation
        try {
            // Create an order from the quote
            $this->validateQuote($this->params['quote']);
            //$this->orderService->execute($params['quote'], $params['cardToken'], $params['agreement']);
            // Temporary workaround for a M2 code T&C checkbox issue not sending data
            $this->orderService->execute(
                $this->params['quote'],
                $this->params['cardToken'],
                array(true)
            );

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
