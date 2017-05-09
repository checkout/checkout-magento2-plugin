<?php

namespace CheckoutCom\Magento2\Model\Adapter;

use InvalidArgumentException;
use CheckoutCom\Magento2\Gateway\Config\Config;

class ChargeAmountAdapter {

    /**
     * Currencies where charge amount is full.
     *
     * @var array
     */
    const FULL_VALUE_CURRENCIES = ['BYR', 'BIF', 'DJF', 'GNF', 'KMF', 'XAF', 'CLF', 'XPF', 'JPY', 'PYG', 'RWF', 'KRW', 'VUV', 'VND', 'XOF'];

    /**
     * Currencies where charge amount is divided by 1000.
     *
     * @var array
     */
    const DIV_1000_VALUE_CURRENCIES = ['BHD', 'KWD', 'OMR', 'JOD'];

    const DIV_1000 = 1000;

    const DIV_100 = 100;

    /**
     * ChargeAmountAdaoter constructor.
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Returns transformed amount by the given currency code which can be handled by the gateway API.
     *
     * @param float $amount Value from the store.
     * @param string $currencyCode
     * @return int
     * @throws InvalidArgumentException
     */
    public static function getGatewayAmountOfCurrency($amount, $currencyCode) {
        $currencyCode = strtoupper($currencyCode);

        if( ! is_numeric($amount) ) {
            throw new InvalidArgumentException('The amount value is not numeric. The [' . $amount . '] value has been given.');
        }

        $amount = (float) $amount;

        if($amount <= 0) {
            throw new InvalidArgumentException('The amount value must be positive. The [' . $amount . '] value has been given.');
        }

        if( in_array($currencyCode, self::FULL_VALUE_CURRENCIES, true) ) {
            return (int) $amount;
        }

        if( in_array($currencyCode, self::DIV_1000_VALUE_CURRENCIES, true) ) {
            return (int) ($amount * self::DIV_1000);
        }

        return (int) ($amount * self::DIV_100);
    }

    /**
     * Returns transformed amount by the given currency code which can be handled by the store.
     *
     * @param string|int $amount Value from the gateway.
     * @param $currencyCode
     * @return float
     */
    public static function getStoreAmountOfCurrency($amount, $currencyCode) {
        $currencyCode   = strtoupper($currencyCode);
        $amount         = (int) $amount;

        if( in_array($currencyCode, self::FULL_VALUE_CURRENCIES, true) ) {
            return (float) $amount;
        }

        if( in_array($currencyCode, self::DIV_1000_VALUE_CURRENCIES, true) ) {
            return (float) ($amount / self::DIV_1000);
        }

        return (float) ($amount / self::DIV_100);
    }

    /**
     * Returns config array for the JS implementation.
     *
     * @return array
     */
    public static function getConfigArray() {
        $data = [];

        foreach(self::FULL_VALUE_CURRENCIES as $currency) {
            $data[ $currency ] = 1;
        }

        foreach(self::DIV_1000_VALUE_CURRENCIES as $currency) {
            $data[ $currency ] = self::DIV_1000;
        }

        $data['others'] = self::DIV_100;

        return $data;
    }

    public static function getPaymentFinalCurrencyCode($orderCurrencyCode) {
        
        // Get the object manager
        $manager = \Magento\Framework\App\ObjectManager::getInstance(); 
        
        // Load the gateway config and get the gateway payment currency
        $gatewayConfig = $manager->create('CheckoutCom\Magento2\Gateway\Config\Config'); 
        $gatewayPaymentCurrency = $gatewayConfig->getPaymentCurrency();

        // Get the user currency display
        $userCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyCode();

        // Load the store currency
        $storeBaseCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseCurrency()->getCode(); 

        // Test the store and gateway config conditions
        if ($gatewayPaymentCurrency == 'base_currency') {

            // Use the store base currency code
            $finalCurrencyCode = $storeBaseCurrencyCode;
        }
        elseif ($gatewayPaymentCurrency == 'order_currency') {

            // Use the order currency code
            $finalCurrencyCode = $userCurrencyCode;
        }
        else {

            // We have a specific currency code to use for the payment
            $finalCurrencyCode = $gatewayPaymentCurrency;
        }

        return $finalCurrencyCode;
    }

    public static function getPaymentFinalCurrencyValue($orderAmount) {
 
        // Get the object manager
        $manager = \Magento\Framework\App\ObjectManager::getInstance(); 
        
        // Load the gateway config and get the gateway payment currency
        $gatewayConfig = $manager->create('CheckoutCom\Magento2\Gateway\Config\Config'); 
        $gatewayPaymentCurrency = $gatewayConfig->getPaymentCurrency();

        // Get the user currency display
        $userCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyCode();

        // Load the store currency
        $storeBaseCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseCurrency()->getCode(); 

        // Create a currency factory
        $currencyFactory = $manager->create('Magento\Directory\Model\CurrencyFactory');
 
        // Test the store and gateway config conditions
        if ($gatewayPaymentCurrency == 'base_currency') {

            // Convert the user currency amount to base currency amount
            $finalAmount = $orderAmount * $currencyFactory->create()->load($userCurrencyCode)->getAnyRate($storeBaseCurrencyCode);            
        }
        elseif ($gatewayPaymentCurrency == 'order_currency') {

            // Do nothing, just use the order currency
            $finalAmount = $orderAmount;
        }
        else {

            // We have a specific currency to use for the payment
            $finalAmount = $orderAmount * $currencyFactory->create()->load($userCurrencyCode)->getAnyRate($gatewayPaymentCurrency);

        }

        return $finalAmount;
    }
}
