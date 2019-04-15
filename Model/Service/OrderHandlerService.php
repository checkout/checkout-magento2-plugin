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
}