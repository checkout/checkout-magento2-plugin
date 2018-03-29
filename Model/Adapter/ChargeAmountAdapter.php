<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Adapter;

use InvalidArgumentException;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Store\Model\StoreManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class ChargeAmountAdapter {

    /**
     * Currency types available in the module configuration.
     */
    const BASE_CURRENCY = 'base_currency';
    const ORDER_CURRENCY = 'order_currency';
    const CUSTOM_CURRENCY = 'custom_currency';

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
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * ChargeAmountAdaoter constructor.
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(GatewayConfig $gatewayConfig, StoreManagerInterface $storeManager, CurrencyFactory $currencyFactory) {
        $this->gatewayConfig = $gatewayConfig;
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
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

        if ( ! is_numeric($amount) ) {
            throw new InvalidArgumentException('The amount value is not numeric. The [' . $amount . '] value has been given.');
        }

        $amount = (float) $amount;

        if ($amount <= 0) {
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
     * Returns a config array for the JS implementation.
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

    /**
     * Returns a converted currency code.
     * @param string $orderCurrencyCode
     * @return string
     */
    public static function getPaymentFinalCurrencyCode($orderCurrencyCode) {
                
        // Get the object manager
        $manager = \Magento\Framework\App\ObjectManager::getInstance();

        // Load the gateway config and get the gateway payment currency
        $gatewayPaymentCurrency = $manager->create('CheckoutCom\Magento2\Gateway\Config\Config')->getPaymentCurrency();

        // Get the user currency display
        $userCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyCode();

        // Load the store currency
        $storeBaseCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseCurrency()->getCode(); 

        // Test the store and gateway config conditions
        if ($gatewayPaymentCurrency == self::BASE_CURRENCY) {

            // Use the store base currency code
            $finalCurrencyCode = $storeBaseCurrencyCode;
        }
        elseif ($gatewayPaymentCurrency == self::ORDER_CURRENCY) {

            // Use the order currency code
            $finalCurrencyCode = $userCurrencyCode;
        }
        else {

            // We have a specific currency code to use for the payment
            $finalCurrencyCode = $manager->create('CheckoutCom\Magento2\Gateway\Config\Config')->getCustomCurrency();
        }

        return $finalCurrencyCode;
    }

    /**
     * Returns a converted currency value.
     * @param float $orderAmount
     * @return float
     */
    public static function getPaymentFinalCurrencyValue($orderAmount) {

        // Get the object manager
        $manager = \Magento\Framework\App\ObjectManager::getInstance();
         
        // Load the gateway config and get the gateway payment currency
        $gatewayPaymentCurrency = $manager->create('CheckoutCom\Magento2\Gateway\Config\Config')->getPaymentCurrency();

        // Get the user currency display
        $userCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyCode();

        // Load the store currency
        $storeBaseCurrencyCode = $manager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseCurrency()->getCode(); 
 
        // Test the store and gateway config conditions
        if ($gatewayPaymentCurrency == self::BASE_CURRENCY) {

            if ($userCurrencyCode == $storeBaseCurrencyCode) {

                // Convert the user currency amount to base currency amount
                $finalAmount = $orderAmount / $manager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($storeBaseCurrencyCode)->getAnyRate($userCurrencyCode);      
            }
            else {
                $finalAmount = $orderAmount;        
            }

        }
        elseif ($gatewayPaymentCurrency == self::ORDER_CURRENCY) {

            if ($userCurrencyCode != $storeBaseCurrencyCode) {

                $finalAmount = $orderAmount * $manager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($storeBaseCurrencyCode)->getAnyRate($userCurrencyCode);            
            } else {
                // Convert the base amount to user currency amount
                $finalAmount = $orderAmount;        
            }
        }
        else {

            if ($userCurrencyCode != $gatewayPaymentCurrency) {
                // We have a specific currency to use for the payment
                $finalAmount = $orderAmount * $manager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($userCurrencyCode)->getAnyRate($gatewayPaymentCurrency);
            } else {
               
               $finalAmount = $orderAmount * $manager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($storeBaseCurrencyCode)->getAnyRate($gatewayPaymentCurrency);
            }
        }

        return $finalAmount;
    }
}
