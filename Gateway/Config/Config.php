<?php

namespace CheckoutCom\Magento2\Gateway\Config;

class Config
{
    protected $loader;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Loader $loader
    ) {
        $this->loader = $loader;
    }

    public function getFrontendConfig() {
        return [
            $this->loader::KEY_PAYMENT => [
                $this->loader::KEY_MODULE_ID => array_merge(
                    $this->getGlobalConfig(),
                    $this->getMethodsConfig()
                )
            ]
        ];
    }

    public function getGlobalConfig() {
        return [
            $this->loader::KEY_CONFIG => $this->loader
            ->data[$this->loader::KEY_SETTINGS][$this->loader::KEY_CONFIG]
        ];        
    }

    public function getMethodsConfig() {
        $methods = [];
        foreach ($this->loader->data[$this->loader::KEY_PAYMENT] as $methodCode => $data) {
            $path = 'payment/' . $methodCode . '/active';
            //if ($this->loader->getValue($path) == 1) {
                $methods[$methodCode] = $data;
            //}
        }

        return $methods;
    }

    public function getValue($path) {
        return $this->loader->getValue($path);
    }
}
