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

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate as QuoteAddressRate;

/**
 * Class ShippingSelector
 */
class ShippingSelector
{
    /**
     * Selects a shipping method.
     *
     * @param Address $address
     *
     * @return string
     */
    public function getShippingMethod(Address $address): ?string
    {
        $address->setCollectShippingRates(true);
        $address->collectShippingRates();
        $shippingRates = $address->getAllShippingRates();

        if (empty($shippingRates)) {
            return null;
        }

        return $this->selectCheapestRate($shippingRates)->getCode();
    }

    /**
     * Selects shipping price with minimal price.
     *
     * @param QuoteAddressRate[] $shippingRates
     *
     * @return QuoteAddressRate
     */
    private function selectCheapestRate(array $shippingRates): QuoteAddressRate
    {
        $rate = array_shift($shippingRates);
        foreach ($shippingRates as $tmpRate) {
            if ($tmpRate->getPrice() < $rate->getPrice()) {
                $rate = $tmpRate;
            }
        }

        return $rate;
    }
}
