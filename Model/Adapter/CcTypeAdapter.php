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
