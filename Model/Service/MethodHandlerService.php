<?php

namespace CheckoutCom\Magento2\Model\Service;

class MethodHandlerService
{
    /**
     * @var Array
     */
    public $instances;

    /**
     * @param MethodHandlerService constructor
     */
    public function __construct($instances)
    {
        $this->instances = $instances;
    }

    public function get($methodId) {
        return $this->instances[$methodId];
    }
}