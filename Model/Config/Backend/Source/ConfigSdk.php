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
 * Class ConfigSdk
 */
class ConfigSdk implements OptionSourceInterface
{

    public const SDK_FRAMES_CONFIG_VALUE = "0";
    public const SDK_FLOW_CONFIG_VALUE = "1";

    public const SDK_FRAMES_CONFIG_LABEL = "Frames";
    public const SDK_FLOW_CONFIG_LABEL = "Flow";

    /**
     * Options getter
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::SDK_FLOW_CONFIG_VALUE,
                'label' => __(self::SDK_FLOW_CONFIG_LABEL)
            ],
            [
                'value' => self::SDK_FRAMES_CONFIG_VALUE,
                'label' => __(self::SDK_FRAMES_CONFIG_LABEL)
            ]
        ];
    }
}
