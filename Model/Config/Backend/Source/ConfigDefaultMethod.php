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

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ConfigDefaultMethod
 */
class ConfigDefaultMethod implements OptionSourceInterface
{
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;

    /**
     * ConfigDefaultMethod constructor
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Return the order status options
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        // Prepare the default array
        $options = [
            'value' => '',
            'label' => __('None')
        ];

        // Get the available payment methods
        $methods = $this->config->getMethodsConfig();

        // Build an array of options
        foreach ($methods as $id => $data) {
            $options[] = [
                'value' => $id,
                'label' => __($data['title'])
            ];
        }

        return $options;
    }
}
