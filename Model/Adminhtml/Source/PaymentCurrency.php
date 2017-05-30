<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Config\Model\Config\Source\Locale\Currency;

class PaymentCurrency implements ArrayInterface {

    const ORDER_CURRENCY = 'order_currency';
    const BASE_CURRENCY = 'base_currency';

    /**
     * @var Currency
     */
    protected $currencyManager;

    /**
     * PaymentCurrency constructor.
     * @param Currency $currency
     */
    public function __construct(Currency $currencyManager){
        $this->currencyManager = $currencyManager;
    }

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

        // Return the options as array
        return array_merge($options, $this->currencyManager->toOptionArray());
    }   
}