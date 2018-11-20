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
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\TokenChargeService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Helper;

class ApplePayPlaceOrder extends AbstractAction {

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
     * @var Helper
     */
    protected $helper;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param GatewayConfig $gatewayConfig
     * @param QuoteManagement $quoteManagement
     * @param Order $orderManager
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        GatewayConfig $gatewayConfig,
        QuoteManagement $quoteManagement,
        CustomerSession $customerSession,
        TokenChargeService $tokenChargeService,
        JsonFactory $resultJsonFactory,
        Helper $helper
    ) {
        parent::__construct($context, $gatewayConfig);

        $this->checkoutSession        = $checkoutSession;
        $this->customerSession        = $customerSession;
        $this->quoteManagement        = $quoteManagement;
        $this->tokenChargeService     = $tokenChargeService;
        $this->resultJsonFactory      = $resultJsonFactory;
        $this->helper                 = $helper;
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
        if ($success) {
            $orderId = $this->createOrder($quote);
        }

        return $this->resultJsonFactory->create()->setData([
            'status' => $success
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
            $quote = $this->helper->prepareGuestQuote($quote);
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
