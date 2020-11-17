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

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

/**
 * Class ConfigDefaultMethod
 */
class ConfigDefaultMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Config
     */
    public $config;

    /**
     * ConfigDefaultMethod constructor.
     *
     * @param Config $config
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Return the order status options
     *
     * @return array
     */
    public function toOptionArray()
    {
        // Prepare the default array
        $options = [
            'value' => '',
            'label' => __('None')
        ];

        // Get the available payment methods
        $methods = $this->config->getMethodsConfig();

        // Build an array of optionss
        foreach ($methods as $id => $data) {
            $options[] = [
                'value' => $id,
                'label' => __($data['title'])
            ];
        }

        return $options;
    }
}
