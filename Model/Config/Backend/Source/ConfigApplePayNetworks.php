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
 * Class ConfigApplePayNetworks
 */
class ConfigApplePayNetworks implements \Magento\Framework\Data\OptionSourceInterface
{

    const CARD_VISA = 'visa';
    const CARD_MASTERCARD = 'masterCard';
    const CARD_AMEX = 'amex';

    /**
     * Possible Apple Pay Cards
     *
     * @return array
     */
    public function toOptionArray()
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
        ];
    }
}
