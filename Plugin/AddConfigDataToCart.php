<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Plugin;

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

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
        if ($result['items'] === []) {
            return $result;
        }

        $configProvider = ['checkoutConfigProvider' => $this->compositeConfigProvider->getConfig()];

        return array_merge($this->config->getMethodsConfig(), $configProvider, $result);
    }
}
