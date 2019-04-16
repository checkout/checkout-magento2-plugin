<?php

namespace CheckoutCom\Magento2\Model\Service;

class OrderHandlerService
{
    protected $checkoutSession;
    protected $config;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
    	\CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
    	$this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * Tasks after place order
     */
    public function afterPlaceOrder($quote, $order)
    {
        // Prepare session quote info for redirection after payment
        $this->checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();
        // Prepare session order info for redirection after payment
        $this->checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }
}