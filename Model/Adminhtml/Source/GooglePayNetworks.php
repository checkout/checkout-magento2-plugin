<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class GooglePayNetworks implements ArrayInterface {

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