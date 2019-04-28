<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Block\Account;

class AddCard extends \Magento\Framework\View\Element\Template {

    /**
     * @var Config
     */
    public $config;

    /**
     * AddCard constructor.
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }
}