<?php

namespace CheckoutCom\Magento2\Gateway\Config;

class Config
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Loader
     */
    public $loader;

    /**
     * Config constructor
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \CheckoutCom\Magento2\Gateway\Config\Loader $loader
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->loader = $loader;
    }

    /**
     * Returns a module config value.
     *
     * @return string
     */
    public function getValue($field, $methodId = null) {
        return $this->loader->getValue($field, $methodId);
    }

    /**
     * Returns a Magento core value.
     *
     * @return string
     */
    public function getCoreValue($path) {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns the module global config.
     *
     * @return array
     */
    public function getModuleConfig() {
        return [
            $this->loader::KEY_CONFIG => $this->loader
            ->data[$this->loader::KEY_SETTINGS][$this->loader::KEY_CONFIG]
        ];        
    }

    /**
     * Returns the payment methods config.
     *
     * @return array
     */
    public function getMethodsConfig() {
        $methods = [];
        foreach ($this->loader->data[$this->loader::KEY_PAYMENT] as $methodId => $data) {
            if ($this->getValue('active', $methodId) == 1) {
                $methods[$methodId] = $data;
            }
        }

        return $methods;
    }

    /**
     * Returns the store name.
     *
     * @return string
     */
    public function getStoreName() {
        $storeName = $this->getCoreValue('general/store_information/name');

        trim($storeName);
        if (empty($storeName)) {
            $storeName = parse_url(
                $this->storeManager->getStore()->getBaseUrl()
            )['host'] ;
        }

        return (string) $storeName;
    }

    /**
     * Determines if the module is in sandbox mode.
     *
     * @return bool
     */
    public function isSandbox() {
        return $this->getValue('environment') == 0;
    }

    /**
     * Determines if the module is in production mode.
     *
     * @return bool
     */
    public function isLive() {
        return $this->getValue('environment') == 1;
    }

    /**
     * Determines if the payment method needs autocapture.
     *
     * @return bool
     */
    public function isAutoCapture($methodId) {
        $value = $this->config->getValue('payment_action', $methodId));
        return $value == 'authorize_capture';
    }
}
