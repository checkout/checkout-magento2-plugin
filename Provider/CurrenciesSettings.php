<?php

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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Provider;

use CheckoutCom\Magento2\Provider\AbstractSettingsProvider;

class CurrenciesSettings extends AbstractSettingsProvider {

    public const CONFIG_CURRENCIES_X1 = 'settings/checkoutcom_configuration/currencies_x1';
    public const CONFIG_CURRENCIES_X1000 = 'settings/checkoutcom_configuration/currencies_x1000';

    public function getCurrenciesX1Table(): array
    {
        return explode(
            ',',
            $this->getCurrenciesX1() ?? ''
        );
    }

    public function getCurrenciesX1(): string
    {
        return $this->getDefaultLevelConfiguration(
            self::CONFIG_CURRENCIES_X1
        ) ?? '';
    }

    public function getCurrenciesX1000Table(): array
    {
        return explode(
            ',',
            $this->getCurrenciesX1000() ?? ''
        );
    }

    public function getCurrenciesX1000(): string
    {
        return $this->getDefaultLevelConfiguration(
            self::CONFIG_CURRENCIES_X1000
        ) ?? '';
    }
}
