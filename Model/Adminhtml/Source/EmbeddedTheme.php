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

class EmbeddedTheme implements ArrayInterface {

    const THEME_STANDARD = 'standard';
    const THEME_SIMPLE = 'simple';

    /**
     * Possible embedded themes
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::THEME_STANDARD,
                'label' => 'Standard',
            ],
            [
                'value' => self::THEME_SIMPLE,
                'label' => 'Simple'
            ]
        ];
    }

}
