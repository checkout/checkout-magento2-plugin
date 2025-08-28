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

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ConfigFlowWidgetDesign
 */
class ConfigFlowWidgetDesignSelector implements OptionSourceInterface
{
    public const PREDEFINED_DESIGN_CONFIG_VALUE = "0";
    public const CUSTOM_DESIGN_CONFIG_VALUE = "1";

    public const PREDEFINED_DESIGN_CONFIG_LABEL = "Pre-defined design";
    public const CUSTOM_DESIGN_CONFIG_LABEL = "Custom design";
    /**
     * Options getter
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' =>self::PREDEFINED_DESIGN_CONFIG_VALUE,
                'label' => __(self::PREDEFINED_DESIGN_CONFIG_LABEL)
            ],
            [
                'value' => self::CUSTOM_DESIGN_CONFIG_VALUE,
                'label' => __(self::CUSTOM_DESIGN_CONFIG_LABEL)
            ],
        ];
    }
}
