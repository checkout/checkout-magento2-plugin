<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Service;

class InstantPurchaseHandlerService
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * InstantPurchaseHandlerService constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->config = $config;
    }
}
