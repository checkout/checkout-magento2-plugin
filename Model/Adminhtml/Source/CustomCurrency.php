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