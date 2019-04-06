<?php

namespace CheckoutCom\Magento2\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{

    const CONFIGURATION_FILE_NAME = 'config.xml';
    const KEY_PAYMENT = 'payment';
    const KEY_ACTIVE = 'active';
    const KEY_CONFIGURATION = 'checkoutcom_configuration';

    protected $scopeConfig;
    protected $loader;


    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \CheckoutCom\Magento2\Gateway\Config\Loader $loader
    ) {
        parent::__construct(
            $scopeConfig,
            $methodCode = null,
            $pathPattern = self::DEFAULT_PATH_PATTERN
        );

        $this->loader = $loader;
    }

    public function getFrontendConfig() {
        return [
            self::KEY_PAYMENT => array_merge(
                $this->getGlobalConfig(),
                $this->getFilteredMethods()
            )
        ];
    }

    public function getGlobalConfig() {
        return [
            self::KEY_CONFIGURATION => $this->loader->data[self::KEY_CONFIGURATION]
        ];        
    }

    public function getFilteredMethods() {
        $methods = [];
        foreach ($this->loader->data[self::KEY_PAYMENT] as $methodCode => $data) {
            $this->setMethodCode($methodCode);
            if ($this->getValue('active') == 1) {
                $methods[$methodCode] = $data;
            }
        }

        return $methods;
    }
}
