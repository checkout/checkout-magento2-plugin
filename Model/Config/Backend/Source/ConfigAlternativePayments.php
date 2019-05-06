<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

/**
 * Class ConfigAlternativePayments
 */
class ConfigAlternativePayments implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * ConfigAlternativePayments  constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->config->getValue('apm_list');
    }
}
