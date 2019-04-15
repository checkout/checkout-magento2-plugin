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

class ConfigGooglePayButton implements \Magento\Framework\Option\ArrayInterface {

    const BUTTON_BLACK = 'black';
    const BUTTON_WHITE = 'white';

    /**
     * Possible Google Pay button styles
     *
     * @return array
     */
    public function toOptionArray() {
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