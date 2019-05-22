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

class ConfigLanguageFallback implements \Magento\Framework\Option\ArrayInterface {

    /**
     * Language fallback
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => 'EN-GB',
                'label' => __('English')
            ],
            [
                'value' => 'ES-ES',
                'label' => __('Spanish')
            ],
            [
                'value' => 'DE-DE',
                'label' => __('German')
            ],            
            [
                'value' => 'KR-KR',
                'label' => __('Korean')
            ],
            [
                'value' => 'FR-FR',
                'label' => __('French')
            ],
            [
                'value' => 'IT-IT',
                'label' => __('Italian')
            ],
            [
                'value' => 'NL-NL',
                'label' => __('Dutch')
            ]
        ];
    }
}