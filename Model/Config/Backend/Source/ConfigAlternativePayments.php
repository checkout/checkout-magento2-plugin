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
        \CheckoutCom\Magento2\Gateway\Config\Loader $configLoader
    )
    {
        $this->configLoader = $configLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->configLoader->data['settings']['checkoutcom_configuration']['apm_list'];
    }
}
