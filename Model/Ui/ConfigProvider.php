<?php

namespace CheckoutCom\Magento2\Model\Ui;

use CheckoutCom\Magento2\Gateway\Config\Config;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param Session $session
     */
    public function __construct(Config $config) {

        $this->config = $config;

    }

    /**
     * Send the configuration to the frontend
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config->getConfig();

    }
}
