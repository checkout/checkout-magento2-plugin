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
     * @var Logger
     */
    protected $logger;

    /**
     * ShippingSelector constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Selects a shipping method.
     *
     * @param Address $address
     * @return Rate
     */
    public function getShippingMethod($address)
    {
        try {
            $address->setCollectShippingRates(true);
            $address->collectShippingRates();
            $shippingRates = $address->getAllShippingRates();

            if (empty($shippingRates)) {
                return null;
            }

            $cheapestRate = $this->selectCheapestRate($shippingRates);
            return $cheapestRate->getCode();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Selects shipping price with minimal price.
     *
     * @param Rate[] $shippingRates
     * @return Rate
     */
    private function selectCheapestRate(array $shippingRates)
    {
        try {
            $rate = array_shift($shippingRates);
            foreach ($shippingRates as $tmpRate) {
                if ($tmpRate->getPrice() < $rate->getPrice()) {
                    $rate = $tmpRate;
                }
            }

            return $rate;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
