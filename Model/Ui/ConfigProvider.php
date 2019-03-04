<?php

namespace CheckoutCom\Magento2\Model\Ui;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Checkout\Model\Session;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Session
     */
    protected $session;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param Session $session
     */
    public function __construct(Config $config, Session $session) {

        $this->config = $config;
        $this->session = $session;

    }

    /**
     * Send the configuration to the frontend
     *
     * @return array
     */
    public function getConfig()
    {


       // \CheckoutCom\Magento2\Helper\Logger::write($this->session);

        return $this->config->getConfig($this->session->getQuote()->getStoreId());
    }
}
