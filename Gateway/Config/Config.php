<?php

namespace CheckoutCom\Magento2\Gateway\Config;

class Config
{
    protected $loader;

    /**
     * Config constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Loader $loader,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    ) {
        $this->loader = $loader;
        $this->quoteHandler = $quoteHandler;
    }

    public function getFrontendConfig() {
        return [
            $this->loader::KEY_PAYMENT => [
                $this->loader::KEY_MODULE_ID => $this->getConfigArray()
            ]
        ];
    }
    
    public function getConfigArray() { 
        return array_merge(
            $this->getModuleConfig(),
            $this->getMethodsConfig(),
            [
                'quote' => $this->quoteHandler->getQuoteData()
            ]
        );
    }

    public function getModuleConfig() {
        return [
            $this->loader::KEY_CONFIG => $this->loader
            ->data[$this->loader::KEY_SETTINGS][$this->loader::KEY_CONFIG]
        ];        
    }

    public function getMethodsConfig() {
        $methods = [];
        foreach ($this->loader->data[$this->loader::KEY_PAYMENT] as $methodCode => $data) {
            $path = 'payment/' . $methodCode . '/active';
            if ($this->loader->getValue($path) == 1) {
                $methods[$methodCode] = $data;
            }
        }

        return $methods;
    }

    public function getValue($path) {
        return $this->loader->getValue($path);
    }
}
