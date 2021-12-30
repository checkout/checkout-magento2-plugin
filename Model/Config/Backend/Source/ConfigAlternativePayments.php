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

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use CheckoutCom\Magento2\Gateway\Config\Loader;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ConfigAlternativePayments
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class ConfigAlternativePayments implements OptionSourceInterface
{
    /**
     * $configLoader field
     *
     * @var Loader $configLoader
     */
    public $configLoader;

    /**
     * ConfigAlternativePayments constructor
     *
     * @param Loader $configLoader
     */
    public function __construct(
        Loader $configLoader
    ) {
        $this->configLoader = $configLoader;
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->configLoader->loadApmList();
    }
}
