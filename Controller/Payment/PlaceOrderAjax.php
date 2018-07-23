<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Model\QuoteManagement;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use Magento\Framework\Controller\Result\JsonFactory;

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
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param GatewayConfig $gatewayConfig
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        GatewayConfig $gatewayConfig,
        CustomerSession $customerSession,
        QuoteManagement $quoteManagement, 
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context, $gatewayConfig);

        $this->checkoutSession   = $checkoutSession;
        $this->customerSession   = $customerSession;
        $this->quoteManagement   = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
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
        $agreement      = array_keys($this->getRequest()->getPostValue('agreement', []));

        // Check for guest email
        if ($quote->getCustomerEmail() === null
            && $this->customerSession->isLoggedIn() === false
            && isset($this->customerSession->getData('checkoutSessionData')['customerEmail'])
        ) 
        {
            $quote->setCustomerId(null)
            ->setCustomerEmail($email)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID)
            ->save();
        }

        // Prepare session quote info for redirection after payment
        $this->checkoutSession
        ->setLastQuoteId($quote->getId())
        ->setLastSuccessQuoteId($quote->getId())
        ->clearHelperData();

        // Set payment
        // todo - use current mode payment tag
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
