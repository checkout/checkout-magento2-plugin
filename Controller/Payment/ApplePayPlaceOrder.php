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
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\TokenChargeService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

class ApplePayPlaceOrder extends AbstractAction {

    const EMAIL_COOKIE_NAME = 'ckoEmail';

    /**
     * @var TokenChargeService
     */
    protected $tokenChargeService;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param GatewayConfig $gatewayConfig
     * @param QuoteManagement $quoteManagement
     * @param Order $orderManager
     * @param JsonFactory $resultJsonFactory
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        GatewayConfig $gatewayConfig,
        QuoteManagement $quoteManagement,
        CustomerSession $customerSession,
        TokenChargeService $tokenChargeService,
        JsonFactory $resultJsonFactory,
        CookieManagerInterface $cookieManager
    ) {
        parent::__construct($context, $gatewayConfig);

        $this->checkoutSession        = $checkoutSession;
        $this->customerSession        = $customerSession;
        $this->quoteManagement        = $quoteManagement;
        $this->tokenChargeService     = $tokenChargeService;
        $this->resultJsonFactory      = $resultJsonFactory;
        $this->cookieManager          = $cookieManager;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {
        // Get the request parameters
        $params = $this->getRequest()->getParams();

        // Get the quote
        $quote = $this->checkoutSession->getQuote();

        // Send the charge request
        //$success = $this->tokenChargeService->sendApplePayChargeRequest($params, $quote);
        $success = false;

        // If charge is successful, create order
        if (!$success) {
            $orderId = $this->createOrder($quote);
        }

        return $this->resultJsonFactory->create()->setData([
            //'status' => $success
            'status' => true
        ]);
    }

    public function createOrder($quote) { 
        // Prepare the quote payment
        $quote->setPaymentMethod(ConfigProvider::CODE_APPLE_PAY);
        $quote->getPayment()->importData(array('method' => ConfigProvider::CODE_APPLE_PAY));

        // Prepare the inventory
        $quote->setInventoryProcessed(false);

        // Prepare session quote info for redirection after payment
        $this->checkoutSession
        ->setLastQuoteId($quote->getId())
        ->setLastSuccessQuoteId($quote->getId())
        ->clearHelperData();

        // Check for guest user quote
        if ($this->customerSession->isLoggedIn() === false)
        {
            // Retrieve the user email 
            $email = $quote->getCustomerEmail() 
            ?? $quote->getBillingAddress()->getEmail()
            ?? $this->cookieManager->getCookie(self::EMAIL_COOKIE_NAME);

            // Set the quote as guest
            $quote->setCustomerId(null)
            ->setCustomerEmail($email)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

            // Delete the cookie
            $this->cookieManager->deleteCookie(self::EMAIL_COOKIE_NAME);
        }
        
        // Create the order
        $order = $this->quoteManagement->submit($quote);

        // Prepare session order info for redirection after payment
        if ($order) {
            $this->checkoutSession->setLastOrderId($order->getId())
                               ->setLastRealOrderId($order->getIncrementId())
                               ->setLastOrderStatus($order->getStatus());

            return $order->getId();
        }

       return false;
    }
}
