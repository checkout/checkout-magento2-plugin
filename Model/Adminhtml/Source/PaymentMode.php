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

class PaymentMode implements ArrayInterface {

    const MODE_CARDS = 'cards';
    const MODE_MIXED = 'mixed';
    const MODE_LOCAL = 'localpayments';

    /**
     * Possible payment modes
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::MODE_CARDS,
                'label' => 'Cards',
            ],
            [
                'value' => self::MODE_MIXED,
                'label' => 'Mixed'
            ],
            [
                'value' => self::MODE_LOCAL,
                'label' => 'Local payments'
            ] 
        ];
    }         
}