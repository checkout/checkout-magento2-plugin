<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\Cart;
use Magento\Store\Model\StoreManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;

class ConfigProvider implements ConfigProviderInterface {

    const CODE = 'checkout_com';
    const CODE_APPLE_PAY = 'checkout_com_applepay';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param Session $checkoutSession
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param Tools $tools
     */
    public function __construct(Config $config, Session $checkoutSession, Cart $cart, StoreManagerInterface $storeManager, Tools $tools) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->tools = $tools;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig() {
        $quote    = $this->cart->getQuote()->collectTotals()->save();
        $quoteCurrency = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return [
            'payment' => [
                'modtag' => $this->tools->modmeta['tag'],
                'modtagapplepay' => $this->tools->modmeta['tagapplepay'],
                'modname' => $this->tools->modmeta['name'],
                $this->tools->modmeta['tag'] => $this->buildConfigData(),
                $this->tools->modmeta['tagapplepay'] => [
                    //'isActive' => $this->config->isActiveApplePay(),
                ],            
            ],
        ];
    }

    private function buildConfigData() {
        // Prepare the output array
        $data = array();

        // Get the config class name
        $className = get_class($this->config);

        // Get the methods available in the class
        $methods = get_class_methods($className);

        // Execute all public methods
        foreach ($methods as $method) {
            // Get the reflection method
            $ref = new \ReflectionMethod($className, $method);

            // Run it if public
            if ($ref->isPublic() && substr($method, 0, 2) !== '__' && $ref->getNumberOfParameters() == 0) {
                $data[$method] =  $this->config->$method();
            }
        }

        return $data;
    }
}
