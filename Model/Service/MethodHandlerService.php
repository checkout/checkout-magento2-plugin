<?php

namespace CheckoutCom\Magento2\Model\Service;

class MethodHandlerService
{

    /**
     * @var Session
     */
    public $methods;

    /**
     * @param MethodHandlerService constructor
     */
    public function __construct(array $data = [])
    {
        $this->methods = $data['methods'];
    }

    public function get($methodId) {
        return $this->methods[$methodId];
    }
}