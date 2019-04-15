<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

class ConfigApplePayNetworks implements \Magento\Framework\Option\ArrayInterface {

    const CARD_VISA = 'visa';
    const CARD_MASTERCARD = 'masterCard';
    const CARD_AMEX = 'amex';

    /**
     * Possible Apple Pay Cards
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
        ];
    }
}