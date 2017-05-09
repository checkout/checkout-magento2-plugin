<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentCurrency implements ArrayInterface {

    const ORDER_CURRENCY = 'order_currency';
    const BASE_CURRENCY = 'base_currency';


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
                'value' => self::ORDER_CURRENCY,
                'label' => 'Order currency'
            ],
            [
                'value' => self::BASE_CURRENCY,
                'label' => 'Base currency'
            ]
        ];

        // Load the object manager 
        $manager = \Magento\Framework\App\ObjectManager::getInstance(); 

        // Create the options list
        $currencies = $manager->create('Magento\Config\Model\Config\Source\Locale\Currency'); 

        // Return the options as array
        return array_merge($options, $currencies->toOptionArray());
    }   
}