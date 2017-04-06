<?php

namespace CheckoutCom\Magento2\Test\Unit\Model\Adapter;

use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use InvalidArgumentException;

class ChargeAmountAdapterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @return array
     */
    public function storeDataProvider() {
        return [
            [25, 'BYR', 25],
            [100, 'XPF', 100],
            [11, 'BHD', 11000],
            [32.4, 'OMR', 32400],
            [4, 'USD', 400],
            [7, 'EUR', 700],
            [25.3, 'PLN', 2530],
            [1111, 'DJF', 1111],
            [12.2, 'VND', 12],
            [3223, 'XOF', 3223],
        ];
    }

    /**
     * @return array
     */
    public function gatewayDataProvider() {
        return [
            [25, 'BYR', 25],
            [100, 'XPF', 100],
            [11000, 'BHD', 11],
            [32400, 'OMR', 32.4],
            [400, 'USD', 4],
            [700, 'EUR', 7],
            [2530, 'PLN', 25.3],
            [1111, 'DJF', 1111],
            [12, 'VND', 12],
            [3223, 'XOF', 3223],
        ];
    }

    /**
     * @return array
     */
    public function exceptionDataProvider() {
        return [
          ['asd', 'BYR', 25],
          ['2x', 'BYR', 25] ,
          ['zero', 'BYR', 25] ,
          ['2,4', 'BYR', 25] ,
          ['-2', 'BYR', 25] ,
        ];
    }

    /**
     * @param $amount
     * @param $currencyCode
     * @param $expected
     * @dataProvider storeDataProvider
     */
    public function testGetGatewayAmountOfCurrency($amount, $currencyCode, $expected) {
        $returned = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);
        self::assertEquals($expected, $returned);
    }

    /**
     * @param $amount
     * @param $currencyCode
     * @param $expected
     * @dataProvider exceptionDataProvider
     * @expectedException InvalidArgumentException
     */
    public function testGetAmountOfCurrencyThrowsException($amount, $currencyCode, $expected) {
        $gatewayReturned = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);
        static::assertEquals($expected, $gatewayReturned);

        $storeReturned = ChargeAmountAdapter::getStoreAmountOfCurrency($amount, $currencyCode);
        static::assertEquals($expected, $storeReturned);
    }


    /**
     * @param $amount
     * @param $currencyCode
     * @param $expected
     * @dataProvider gatewayDataProvider
     */
    public function testGetMagentoAmountOfCurrency($amount, $currencyCode, $expected) {
        $returned = ChargeAmountAdapter::getStoreAmountOfCurrency($amount, $currencyCode);
        static::assertEquals($expected, $returned);
    }

}
