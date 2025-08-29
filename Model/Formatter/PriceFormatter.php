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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Formatter;

use CheckoutCom\Magento2\Provider\CurrenciesSettings;

class PriceFormatter
{
    protected CurrenciesSettings $currenciesSettings;

    public function __construct(
        CurrenciesSettings $currenciesSettings,
    ) {
        $this->currenciesSettings = $currenciesSettings;
    }

    public function getFormattedPrice(string|int|float $amount, string $currency) 
    {
        $currenciesX1 = $this->currenciesSettings->getCurrenciesX1Table();
        $currenciesX1000 = $this->currenciesSettings->getCurrenciesX1000Table();
        $amount = round((float) $amount * 100) / 100;

        if (in_array($currency, $currenciesX1)) {
            return $amount;
        }

        if (in_array($currency, $currenciesX1000)) {
            return $amount * 1000;
        }
            
        return $amount * 100;
    }
}
