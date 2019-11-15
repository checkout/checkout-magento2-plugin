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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\InstantPurchase;

/**
 * Class ShippingSelector
 */
class ShippingSelector
{
    /**
     * Selects a shipping method.
     *
     * @param Address $address
     * @return Rate
     */
    public function getShippingMethod($address)
    {
        $address->setCollectShippingRates(true);
        $address->collectShippingRates();
        $shippingRates = $address->getAllShippingRates();

        if (empty($shippingRates)) {
            return null;
        }

        $cheapestRate = $this->selectCheapestRate($shippingRates);
        return $cheapestRate->getCode();
    }

    /**
     * Selects shipping price with minimal price.
     *
     * @param Rate[] $shippingRates
     * @return Rate
     */
    private function selectCheapestRate(array $shippingRates)
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
