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
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param Session $session
     * @param CustomerSession $customerSession
     * @param GatewayConfig $gatewayConfig
     * @param OrderService $orderService
     */
    public function __construct(
        Context $context,
        Session $session,
        CustomerSession $customerSession,
        GatewayConfig $gatewayConfig,
        OrderService $orderService
    ) {
        parent::__construct($context, $gatewayConfig);

        $this->session          = $session;
        $this->customerSession  = $customerSession;
        $this->orderService     = $orderService;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {

        // Retrieve the request parameters
        $resultRedirect = $this->getResultRedirect();
        $cardToken      = $this->getRequest()->getParam('cko-card-token');
        $email          = $this->getRequest()->getParam('cko-context-id');
        $agreement      = array_keys($this->getRequest()->getPostValue('agreement', []));
        $quote          = $this->session->getQuote();

        if ($quote->getCustomerEmail() === null
            && $this->customerSession->isLoggedIn() === false
            && isset($this->customerSession->getData('checkoutSessionData')['customerEmail'])
            && $this->customerSession->getData('checkoutSessionData')['customerEmail'] === $email
        ) {
            $quote->setCustomerEmail($email);
        }

        // Perform quote and order validation
        try {

            // Create an order from the quote
            $this->validateQuote($quote);
            $this->orderService->execute($quote, $cardToken, $agreement);

            // 3D Secure redirection if needed
            if($this->gatewayConfig->isVerify3DSecure()) {
                $this->place3DSecureRedirectUrl();
                exit();
            }

            return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }

    /**
     * Listens to a session variable set in Gateway/Response/ThreeDSecureDetailsHandler.php.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function place3DSecureRedirectUrl() {
        echo '<script type="text/javascript">';
        echo 'function waitForElement() {';
        echo 'var redirectUrl = "' . $this->session->get3DSRedirect() . '";';
        echo 'if (redirectUrl.length != 0){ window.location.replace(redirectUrl); }';
        echo 'else { setTimeout(waitForElement, 250); }';
        echo '} ';
        echo 'waitForElement();';
        echo '</script>';
    }
}
