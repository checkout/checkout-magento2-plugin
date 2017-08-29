<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Config\Model\Config\Source\Locale\Currency;

class CustomCurrency implements ArrayInterface {

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
        // Return the options as array
        return $this->currencyManager->toOptionArray();
    }   
}