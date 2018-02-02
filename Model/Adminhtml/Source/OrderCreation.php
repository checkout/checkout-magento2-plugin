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

class OrderCreation implements ArrayInterface {

    const BEFORE_AUTH = 'before_auth';
    const AFTER_AUTH = 'after_auth';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::BEFORE_AUTH,
                'label' => 'Before authorization'
            ],
            [
                'value' => self::AFTER_AUTH,
                'label' => 'After authorization'
            ]        
        ];
    }

}