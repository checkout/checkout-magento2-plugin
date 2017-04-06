<?php

namespace CheckoutCom\Magento2\Test\Unit\Model\Adapter;

use CheckoutCom\Magento2\Model\Adapter\CcTypeAdapter;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CcTypeAdapterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $gatewayConfig;

    /**
     * @var CcTypeAdapter
     */
    private $ccTypeAdapter;

    /**
     * @var array
     */
    static protected $cardTypes = [
        'amex'          => 'AE',
        'visa'          => 'VI',
        'mastercard'    => 'MC',
        'discover'      => 'DI',
        'jcb'           => 'JCB',
        'maestro'       => 'SM',
        'diners'        => 'DN',
        'dinersclub'    => 'DN',
    ];
    
    protected function setUp() {
        $objectManager = new ObjectManager($this);
        
        $this->gatewayConfig = $this->getMockBuilder(GatewayConfig::class)
                ->disableOriginalConstructor()
                ->getMock();
        
        $this->ccTypeAdapter = $objectManager->getObject(
                CcTypeAdapter::class,
                [
                    'config' => $this->gatewayConfig
                ]
            );
    }

    /**
     * @return array
     */
    public function ccProvider() {
        return [
            ['Amex', 'AE'],
            ['Visa', 'VI'],
            ['MasterCard', 'MC'],
            ['Discover', 'DI'],
            ['jcb', 'JCB'],
            ['Maestro', 'SM'],
            ['Diners', 'DN'],
            ['Unreconigzed', 'OT'],
        ];
    }

    /**
     * @param $type
     * @param $expected
     * @dataProvider ccProvider
     */
    public function testGetFromGateway($type, $expected) {
        $this->gatewayConfig->method('getCcTypesMapper')->willReturn(self::$cardTypes);
         
        $returned = $this->ccTypeAdapter->getFromGateway($type);
        static::assertEquals($expected, $returned);
    }
    
}
