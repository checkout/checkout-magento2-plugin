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

use CheckoutCom\Magento2\Gateway\Config\Loader;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 */
class Config
{
    /**
     * @var Repository
     */
    public $assetRepository;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var Loader
     */
    public $loader;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * Config constructor
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\RequestInterface $request,
        \CheckoutCom\Magento2\Gateway\Config\Loader $loader,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->assetRepository = $assetRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->loader = $loader;
        $this->utilities = $utilities;
    }

    /**
     * Checks if an external request is valid.
     */
    public function isValidAuth($type, $header = null)
    {
        // Get the authorization header
        if ($header) {
            $key = $this->request->getHeader($header);
        } else {
            $key = $this->request->getHeader('Authorization');
        }

        // Validate the header
        switch ($type) {
            case 'pk':
                return $this->isValidPublicKey($key);

            case 'psk':
                return $this->isValidPrivateSharedKey($key);
        }
    }

    /**
     * Checks if a private shared key request is valid.
     */
    public function isValidPrivateSharedKey($key)
    {
        // Get the private shared key from config
        $privateSharedKey = $this->getValue('private_shared_key');

        // Return the validity check
        return $key == $privateSharedKey
        && $this->request->isPost();
    }

    /**
     * Checks if a public key is valid.
     */
    public function isValidPublicKey($key)
    {
        // Get the public key from config
        $publicKey = $this->getValue('public_key');

        // Return the validity check
        return $key == $publicKey
        && $this->request->isPost();
    }

    /**
     * Returns a module config value.
     *
     * @return string
     */
    public function getValue(
        $field,
        $methodId = null,
        $storeCode = null,
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    ) {
        return $this->loader->init()->getValue($field, $methodId, $storeCode, $scope);
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
            Loader::KEY_CONFIG => $this->loader->init()
                ->data[Loader::KEY_SETTINGS][Loader::KEY_CONFIG]
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
        if ($this->canDisplay()) {
            foreach ($this->loader->init()->data[Loader::KEY_PAYMENT] as $methodId => $data) {
                if ($this->getValue('active', $methodId) == 1) {
                    $methods[$methodId] = $data;
                }
            }
        }

        return $methods;
    }

    /**
     * Returns the payment methods list.
     *
     * @return array
     */
    public function getMethodsList()
    {
        $methods = [];
        if ($this->canDisplay()) {
            foreach ($this->loader->init()->data[Loader::KEY_PAYMENT] as $methodId => $data) {
                if ($this->getValue('active', $methodId) == 1) {
                    $methods[] = $methodId;
                }
            }
        }

        return $methods;
    }

    /**
     * Checks if payment options can be displayed.
     *
     * @return bool
     */
    public function canDisplay()
    {
        // Get the account keys
        $accountKeys = $this->getAccountKeys();

        // Return the check result
        return $this->getValue('active') == 1
        && !in_array('', array_map('trim', $accountKeys));
    }

    /**
     * Gets the account keys.
     *
     * @return array
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
        // Get the capture time from config and covert from hours to seconds
        $captureTime = $this->getValue('capture_time');
        $captureTime *= 3600;

        // Force capture time to a minimum of 36 seconds
        $min = $this->getValue('min_capture_time');
        $captureTime = $captureTime >= $min ? $captureTime : $min;

        // Check the setting
        if ($this->needsAutoCapture()) {
            // Calculate the capture date
            $captureDate = time() + $captureTime;
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

    public function getStoreLanguage()
    {
        $storeId =  $this->storeManager->getStore()->getId();

        return  $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
     * Gets the Alternative Payments.
     *
     * @return array
     */
    public function getApms()
    {
        return $this->loader->init()->loadApmList();
    }

    /**
     * Gets the module images path.
     *
     * @return array
     */
    public function getImagesPath()
    {
        return $this->assetRepository
        ->getUrl('CheckoutCom_Magento2::images');
    }

    /**
     * Gets the module CSS path.
     *
     * @return array
     */
    public function getCssPath()
    {
        return $this->assetRepository
        ->getUrl('CheckoutCom_Magento2::css');
    }

    /**
     * Determines if risk rules should be enabled for a payment request.
     *
     * @return bool
     */
    public function needsRiskRules($methodId)
    {
        return ((bool) !$this->getValue('risk_rules_enabled', $methodId) === true);
    }
}
