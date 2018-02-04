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

class Frames implements ArrayInterface {

    const FRAMES_FORM = 'form';
    const FRAMES_AP = 'ap';
    const FRAMES_BOTH = 'both';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::FRAMES_FORM,
                'label' => __('Payment form')
            ],
            [
                'value' => self::FRAMES_AP,
                'label' => __('Alternative Payments')
            ],     
            [
                'value' => self::FRAMES_BOTH,
                'label' => __('Payment form and Alternative Payments')
            ]
        ];
    }

}