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

class GooglePayEnvironment implements ArrayInterface {

    const ENVIRONMENT_TEST = 'TEST';
    const ENVIRONMENT_PRODUCTION = 'PRODUCTION';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::ENVIRONMENT_TEST,
                'label' => __('Test')
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }

}
