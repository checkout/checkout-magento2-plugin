<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Ui;

use CheckoutCom\Magento2\Gateway\Config\Loader;

/**
 * Class ConfigProvider.
 */
class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandlerService;

    /**
     * ConfigProvider constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Send the configuration to the frontend
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            Loader::KEY_PAYMENT => [
                Loader::KEY_MODULE_ID => $this->getConfigArray()
            ]
        ];
    }

    /**
     * Returns a merged array of config values.
     *
     * @return array
     */
    public function getConfigArray()
    {
        return array_merge(
            $this->config->getModuleConfig(),
            $this->config->getMethodsConfig(),
            [
                'checkoutcom_data' => [
                    'quote' => $this->quoteHandler->getQuoteData(),
                    'store' => [
                        'name' => $this->config->getStoreName()
                    ],
                    'user' => [
                        'hasCards' => $this->vaultHandler->userHasCards()
                    ]
                ]
            ]
        );
    }
}
