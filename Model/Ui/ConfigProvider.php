<?php

namespace CheckoutCom\Magento2\Model\Ui;

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
            $this->config->loader::KEY_PAYMENT =>
            [
                $this->config->loader::KEY_MODULE_ID => $this->getConfigArray()
            ]
        ];
    }

    /**
     * Returns a merged array of config values.
     *
     * @return array
     */
    protected function getConfigArray() { 
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