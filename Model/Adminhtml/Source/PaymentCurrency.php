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

class PaymentCurrency implements ArrayInterface {

    const ORDER_CURRENCY = 'order_currency';
    const BASE_CURRENCY = 'base_currency';
    const CUSTOM_CURRENCY = 'custom_currency';

    /**
     * Options provider function
     *
     * @return array
     */
    public function toOptionArray() {
        return $this->getPaymentCurrencyOptions();
    }

    /**
     * Get the payment currency options
     *
     * @return array
     */
    public function getPaymentCurrencyOptions()
    { 
        // Create the base options
        $options = [
            [
                'value' => self::BASE_CURRENCY,
                'label' => 'Use Magento default'
            ],
            [
                'value' => self::ORDER_CURRENCY,
                'label' => 'Order currency'
            ],
            [
                'value' => self::CUSTOM_CURRENCY,
                'label' => 'Custom currency'
            ],
        ];

        // Return the options as array
        return $options;
    }   
}