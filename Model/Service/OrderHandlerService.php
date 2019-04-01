<?php

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;

class OrderHandlerService
{

    protected $config;

    /**
     * @param Context $context
     */
    public function __construct(Config $config)
    {

        $this->config = $config;

    }

}