<?php

namespace CheckoutCom\Magento2\Model\Adapter;

use InvalidArgumentException;

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
     * Returns transformed amount by the given currency code which can be handles by the gateway API.
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
     * Returns transformed amount by the given currency code which can be handles by the store.
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

}
