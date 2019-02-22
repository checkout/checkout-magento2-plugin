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
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Model\Service\OrderService;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Helper\Helper;

class PlaceOrderAjax extends AbstractAction {

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

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
     * @param GatewayConfig $gatewayConfig
     * @param OrderService $orderService
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        GatewayConfig $gatewayConfig,
        OrderService $orderService,
        CustomerSession $customerSession,
        QuoteManagement $quoteManagement, 
        JsonFactory $resultJsonFactory,
        Helper $helper
    ) {
        parent::__construct($context, $gatewayConfig);

        $this->checkoutSession   = $checkoutSession;
        $this->customerSession   = $customerSession;
        $this->orderService      = $orderService;
        $this->quoteManagement   = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper            = $helper;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {
        // Prepare the redirection
        $resultRedirect = $this->getResultRedirect();

        // Load the customer quote
        $quote          = $this->checkoutSession->getQuote();

        // Retrieve the request parameters
        $cardToken      = $this->getRequest()->getParam('cko-card-token');
        $email          = $this->getRequest()->getParam('cko-context-id');
        $agreement      = array_keys($this->getRequest()->getPostValue('agreement', []));

        // Check for guest email
        if ($this->customerSession->isLoggedIn() === false) {
            $quote = $this->helper->prepareGuestQuote($quote);
        }

        // Prepare session quote info for redirection after payment
        $this->checkoutSession
        ->setLastQuoteId($quote->getId())
        ->setLastSuccessQuoteId($quote->getId())
        ->clearHelperData();

        // Set payment
        $quote->getPayment()->setMethod('substitution');
        $quote->collectTotals()->save();

        // Create the order
        $order = $this->quoteManagement->submit($quote);

        // Prepare session order info for redirection after payment
        if ($order) {
            $this->checkoutSession->setLastOrderId($order->getId())
                               ->setLastRealOrderId($order->getIncrementId())
                               ->setLastOrderStatus($order->getStatus());
        }
        
        return $this->resultJsonFactory->create()->setData([
            'trackId' => $order->getIncrementId()
        ]);
    }
}
