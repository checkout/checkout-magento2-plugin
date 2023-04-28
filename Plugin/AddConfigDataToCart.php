<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Plugin;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\CompositeConfigProvider;

class AddConfigDataToCart
{
    private Config $config;
    private CompositeConfigProvider $compositeConfigProvider;

    public function __construct(
        Config $config,
        CompositeConfigProvider $compositeConfigProvider
    ) {
        $this->config = $config;
        $this->compositeConfigProvider = $compositeConfigProvider;
    }

    public function afterGetSectionData(Cart $subject, array $result): array
    {
        $configProvider = ['checkoutConfigProvider' => $this->compositeConfigProvider->getConfig()];

        return array_merge($this->config->getMethodsConfig(), $configProvider, $result);
    }
}
