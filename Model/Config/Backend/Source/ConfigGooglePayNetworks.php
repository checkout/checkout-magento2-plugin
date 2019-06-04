<?php

/**
 * Checkout.com
 * Authorised and regulated as an electronic money institution
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

class ConfigGooglePayNetworks implements \Magento\Framework\Option\ArrayInterface {

    const CARD_VISA = 'VISA';
    const CARD_MASTERCARD = 'MASTERCARD';
    consT CARD_AMEX = 'AMEX';
    consT CARD_JCB = 'JCB';
    consT CARD_DISCOVER = 'DISCOVER';

    /**
     * Possible Google Pay Cards
     *
     * @return array
     */
    public function toOptionArray() {
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
                'value' => self::CARD_JCB,
                'label' => __('JCB')
            ],
            [
                'value' => self::CARD_DISCOVER,
                'label' => __('Discover')
            ],
        ];
    }

}