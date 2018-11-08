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

class ApplePayApiMode implements ArrayInterface {

    const REQUEST_API = 'requestapi';
    const APPLE_PAY_JS = 'applepayjs';

    /**
     * Possible Apple Pay APIs
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::REQUEST_API,
                'label' => __('Merchant Request API')
            ],
            [
                'value' => self::APPLE_PAY_JS,
                'label' => __('Apple Pay JS')
            ]
        ];
    }

}