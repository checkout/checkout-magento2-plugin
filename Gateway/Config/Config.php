<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Payment\Gateway\Config\Config as BaseConfig;


class Config extends BaseConfig {

    protected $storeManager;
    protected $scopeConfig;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }


    public function getTitle() {
        return (string) $this->scopeConfig->getValue(
            'payment/checkoutcom_gateway/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}