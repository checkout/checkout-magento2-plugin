<?php

namespace CheckoutCom\Magento2\Model\Service;

class MethodHandlerService
{
    /**
     * @param Context $context
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get($methodId) {
        return $this->data[$methodId];
    }
}