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

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use CheckoutCom\Magento2\Gateway\Config\Loader;
use Magento\Framework\Data\OptionSourceInterface;

class ConfigFlowAlternativePayments implements OptionSourceInterface
{
    private Loader $configLoader;

    public function __construct(
        Loader $configLoader
    ) {
        $this->configLoader = $configLoader;
    }

    /**
     * {@inheritDoc}
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return $this->configLoader->loadApmList(Loader::APM_FLOW_FILE_NAME);
    }
}
