<?php

namespace CheckoutCom\Magento2\Model\Service;

class ShopperHandlerService
{
    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * ShopperHandlerService constructor
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->cookieManager = $cookieManager;
        $this->config = $config;
    }

    /**
     * Find a customer email
     */
    public function findEmail($quote)
    {
        return $quote->getCustomerEmail()
        ?? $quote->getBillingAddress()->getEmail()
        ?? $this->cookieManager->getCookie(
            $this->config->getValue('email_cookie_name')
        );
    }
}