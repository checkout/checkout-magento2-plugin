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
     * @var RequestInterface
     */
    protected $request;

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
        \Magento\Framework\App\RequestInterface $request,
        \CheckoutCom\Magento2\Gateway\Config\Loader $loader,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->loader = $loader;
        $this->utilities = $utilities;
    }

	/**
     * Checks if an external request is valid.
     */
    public function isValidAuth() {
        // Get the authorization header
        $authorization = $this->request->getHeader('Authorization');

        // Get the secret key from config
        $privateSharedKey = $this->getValue('private_shared_key');
        
        // Return the validity check
        return $authorization == $privateSharedKey;
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
            // Check if the method is active
            if ($this->getValue('active', $methodId) == 1) {
                $methods[$methodId] = $data;
            }
        }

        return $methods;
    }

    /**
     * Determines if 3DS should be enabled for a payment request.
     *
     * @return string
     */
    public function needs3ds($methodId) {
        return (((bool) $this->getValue('three_ds', $methodId) === true) 
        || ((bool) $this->getValue('mada_enabled', $methodId) === true));
    }

	/**
     * Checks and sets a capture time for the request.
     */
    public function getCaptureTime($methodId) {
        // Get the capture time from config
        $captureTime = $this->getValue('capture_time', $methodId);

        // Check the setting
        if ($this->needsAutoCapture($methodId) && !empty($captureTime)) {
            // Calculate the capture date
            $captureDate = time() + $captureTime*60*60;

            return $this->utilities->formatDate($captureDate);
        }

        return false;
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
     * Returns the store name.
     *
     * @return string
     */
    public function getStoreUrl() {
        return $this->storeManager->getStore()->getBaseUrl();
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
     * Determines if the payment method needs auto capture.
     *
     * @return bool
     */
    public function needsAutoCapture($methodId) {
        return ($this->getValue('payment_action', $methodId) == 'authorize_capture'
        || (bool) $this->getValue('mada_enabled', $methodId) === true);
    }

    /**
     * Get the MADA BIN file.
     *
     * @return bool
     */
    public function getMadaBinFile() {
        return (int) $this->getValue('environment') == 1
        ? $this->getValue('mada_test_file') : $this->getValue('mada_live_file');
    }

}
