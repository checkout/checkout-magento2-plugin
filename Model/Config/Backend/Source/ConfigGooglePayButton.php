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
 * Class ConfigGooglePayButton
 */
class ConfigGooglePayButton implements OptionSourceInterface
{
    /**
     * BUTTON_BLACK constant
     *
     * @var string BUTTON_BLACK
     */
    const BUTTON_BLACK = 'black';
    /**
     * BUTTON_WHITE constant
     *
     * @var string BUTTON_WHITE
     */
    const BUTTON_WHITE = 'white';

    /**
     * Possible Google Pay button styles
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::BUTTON_BLACK,
                'label' => __('Black')
            ],
            [
                'value' => self::BUTTON_WHITE,
                'label' => __('White')
            ],
        ];
    }
}
