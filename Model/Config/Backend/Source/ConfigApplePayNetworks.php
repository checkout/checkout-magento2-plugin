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
 * Class ConfigApplePayNetworks
 */
class ConfigApplePayNetworks implements OptionSourceInterface
{
    /**
     * CARD_VISA constant
     *
     * @var string CARD_VISA
     */
    const CARD_VISA = 'visa';
    /**
     * CARD_MASTERCARD constant
     *
     * @var string CARD_MASTERCARD
     */
    const CARD_MASTERCARD = 'masterCard';
    /**
     * CARD_AMEX constant
     *
     * @var string CARD_AMEX
     */
    const CARD_AMEX = 'amex';
    /**
     * CARD_MADA constant
     *
     * @var string CARD_MADA
     */
    const CARD_MADA = 'mada';

    /**
     * Possible Apple Pay Cards
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::CARD_VISA,
                'label' => __('Visa')
            ],
            [
                'value' => self::CARD_MASTERCARD,
                'label' => __('Mastercard')
            ],
            [
                'value' => self::CARD_AMEX,
                'label' => __('American Express')
            ],
            [
                'value' => self::CARD_MADA,
                'label' => __('MADA')
            ],
        ];
    }
}
