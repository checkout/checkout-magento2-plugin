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

namespace CheckoutCom\Magento2\Gateway\Config;

/**
 * Class Config
 */
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
    public function isValidAuth()
    {
        // Get the authorization header
        $authorization = $this->request->getHeader('Authorization');

        // Get the secret key from config
        $privateSharedKey = $this->getValue('private_shared_key');

        // Return the validity check
        return $authorization == $privateSharedKey
        && $this->request->isPost();
    }

    /**
     * Returns a module config value.
     *
     * @return string
     */
    public function getValue($field, $methodId = null)
    {
        return $this->loader->getValue($field, $methodId);
    }

    /**
     * Returns a Magento core value.
     *
     * @return string
     */
    public function getCoreValue($path)
    {
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
    public function getModuleConfig()
    {
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
    public function getMethodsConfig()
    {
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
     * Gets the account keys.
     *
     * @return string
     */
    public function getAccountKeys($methodId = null)
    {

        // Get the account keys for a method
        if ($methodId) {
            $publicKey = $this->getValue('public_key', $methodId);
            $secretKey = $this->getValue('secret_key', $methodId);
            $privateSharedKey = $this->getValue('private_shared_key', $methodId);
            if (!empty($publicKey)
                && !empty($secretKey)
                && !empty($privateSharedKey)
                && !$this->getValue('use_default_account', $methodId)
            ) {
                return [
                    'public_key' => $publicKey,
                    'secretKey' => $secretKey,
                    'privateSharedKey' => $privateSharedKey
                ];
            }
        }

        // Return the default account keys
        return [
            'public_key' => $this->getValue('public_key'),
            'secret_key' => $this->getValue('secret_key'),
            'private_shared_key' => $this->getValue('private_shared_key')
        ];
    }

    /**
     * Determines if 3DS should be enabled for a payment request.
     *
     * @return string
     */
    public function needs3ds($methodId)
    {
        return (((bool) $this->getValue('three_ds', $methodId) === true)
        || ((bool) $this->getValue('mada_enabled', $methodId) === true));
    }

    /**
     * Checks and sets a capture time for the request.
     *
     * @return string
     */
    public function getCaptureTime()
    {
        // Get the capture time from config
        $captureTime = (float) $this->getValue('capture_time');
        
        // Force capture time to a minimum of 10 seconds
        $min = 0.0027;
        $captureTime = $captureTime >= 0.0027 ? $captureTime : $min;

        // Check the setting
        if ($this->needsAutoCapture()) {
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
    public function getStoreName()
    {
        $storeName = $this->getCoreValue('general/store_information/name');
        trim($storeName);
        return !empty($storeName) ? $storeName
        : $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Returns the store name.
     *
     * @return string
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Determines if the module is in production mode.
     *
     * @return bool
     */
    public function isLive()
    {
        return $this->getValue('environment') == 1;
    }

    /**
     * Determines if the payment method needs auto capture.
     *
     * @return bool
     */
    public function needsAutoCapture()
    {
        return ($this->getValue('payment_action') == 'authorize_capture'
        || (bool) $this->getValue('mada_enabled', 'checkoutcom_card_payment') === true);
    }

    /**
     * Determines if dynamic descriptors are available.
     *
     * @return bool
     */
    public function needsDynamicDescriptor()
    {
        return $this->getValue('dynamic_descriptor_enabled')
        && !empty($this->getValue('descriptor_name'))
        && !empty($this->getValue('descriptor_city'));
    }

    /**
     * Get the MADA BIN file.
     *
     * @return bool
     */
    public function getMadaBinFile()
    {
        return (int) $this->getValue('environment') == 1
        ? $this->getValue('mada_test_file') : $this->getValue('mada_live_file');
    }

    /**
     * Gets the apms.
     *
     * @return <array>  The apms.
     */
    public function getApms()
    {
        return $this->loader->loadApmList();
    }
}
