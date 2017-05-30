<?php

namespace CheckoutCom\Magento2\Model\Adapter;

use CheckoutCom\Magento2\Gateway\Config\Config;

class CcTypeAdapter {

    /**
     * @var Config
     */
    protected $config;

    /**
     * CcTypeAdapter constructor.
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Returns Credit Card type for a store.
     *
     * @param string $type
     * @return string
     */
    public function getFromGateway($type) {
        $mapper = $this->config->getCcTypesMapper();
        $type   = strtolower($type);

        if( array_key_exists($type, $mapper) ) {
            return $mapper[$type];
        }

        return 'OT';
    }

}
