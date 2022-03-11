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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Gateway\Config;

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Config
 */
class Config
{
    /**
     * $assetRepository field
     *
     * @var Repository $assetRepository
     */
    private $assetRepository;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * $request field
     *
     * @var RequestInterface $request
     */
    private $request;
    /**
     * $loader field
     *
     * @var Loader $loader
     */
    private $loader;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;

    /**
     * Config constructor
     *
     * @param Repository            $assetRepository
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface  $scopeConfig
     * @param RequestInterface      $request
     * @param Loader                $loader
     * @param Utilities             $utilities
     * @param Logger                $logger
     */
    public function __construct(
        Repository $assetRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        Loader $loader,
        Utilities $utilities,
        Logger $logger
    ) {
        $this->assetRepository = $assetRepository;
        $this->storeManager    = $storeManager;
        $this->scopeConfig     = $scopeConfig;
        $this->request         = $request;
        $this->loader          = $loader;
        $this->utilities       = $utilities;
        $this->logger          = $logger;
    }

    /**
     * Checks if an external request is valid
     *
     * @param string      $type
     * @param string|null $header
     *
     * @return bool
     */
    public function isValidAuth(string $type, string $header = null): bool
    {
        // Get the authorization header
        if ($header) {
            $key = $this->request->getHeader($header);
        } else {
            $key = $this->request->getHeader('Authorization');
        }

        $this->logger->additional('authorization header: ' . $key, 'auth');

        // Validate the header
        switch ($type) {
            case 'pk':
                return $this->isValidPublicKey($key);

            case 'psk':
                return $this->isValidPrivateSharedKey($key);
        }

        return false;
    }

    /**
     * Checks if a private shared key request is valid
     *
     * @param string|false $key
     *
     * @return bool
     */
    public function isValidPrivateSharedKey($key): bool
    {
        // Get the private shared key from config
        $privateSharedKey = $this->getValue('private_shared_key');
        $this->logger->additional('private shared key: ' . $privateSharedKey, 'auth');

        // Return the validity check
        return $key === $privateSharedKey && $this->request->isPost();
    }

    /**
     * Checks if a public key is valid
     *
     * @param string|false $key
     *
     * @return bool
     */
    public function isValidPublicKey($key): bool
    {
        // Get the public key from config
        $publicKey = $this->getValue('public_key');
        $this->logger->additional('public key: ' . $publicKey, 'auth');

        // Return the validity check
        return $key === $publicKey && $this->request->isPost();
    }

    /**
     * Returns a module config value
     *
     * @param string           $field
     * @param string|null      $methodId
     * @param string|int|null  $storeCode
     * @param string|null      $scope
     *
     * @return mixed
     */
    public function getValue(
        string $field,
        string $methodId = null,
        $storeCode = null,
        string $scope = ScopeInterface::SCOPE_STORE
    ) {
        return $this->loader->getValue($field, $methodId, $storeCode, $scope);
    }

    /**
     * Returns a Magento core value
     *
     * @param string $path
     *
     * @return mixed
     */
    public function getCoreValue(string $path)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns the module global config.
     *
     * @return mixed[]
     */
    public function getModuleConfig(): array
    {
        /** @var mixed[] $moduleConfig */
        $moduleConfig = $this->scopeConfig->getValue('settings/checkoutcom_configuration');
        if (array_key_exists('secret_key', $moduleConfig)) {
            unset($moduleConfig['secret_key']);
        }
        if (array_key_exists('private_shared_key', $moduleConfig)) {
            unset($moduleConfig['private_shared_key']);
        }

        return [
            Loader::KEY_CONFIG => $moduleConfig,
        ];
    }

    /**
     * Returns the payment methods config.
     *
     * @return string[]
     */
    public function getMethodsConfig(): array
    {
        $output = [];
        /** @var mixed[] $paymentMethodsConfig */
        $paymentMethodsConfig = $this->scopeConfig->getValue(Loader::KEY_PAYMENT);

        /**
         * Get only the active CheckoutCom methods
         *
         * @var string $key
         * @var string[] $method
         */
        foreach($paymentMethodsConfig as $key => $method) {
            if (false !== strpos($key, 'checkoutcom')
               && isset($method['active'])
               && (int)$method['active'] === 1
            ) {
                if (array_key_exists('private_shared_key', $method)) {
                    unset($method['private_shared_key']);
                }
                if (array_key_exists('secret_key', $method)) {
                    unset($method['secret_key']);
                }
                $output[$key] = $method;
            }
        }

        return $output;
    }

    /**
     * Returns the payment methods list.
     *
     * @return string[][]
     */
    public function getMethodsList(): array
    {
        if ($this->canDisplay()) {
            return array_keys($this->getMethodsConfig());
        }

        return [];
    }

    /**
     * Checks if payment options can be displayed.
     *
     * @return bool
     */
    public function canDisplay(): bool
    {
        // Get the account keys
        $accountKeys = $this->getAccountKeys();

        // Return the check result
        return $this->getValue('active') == 1 && !in_array('', array_map('trim', $accountKeys));
    }

    /**
     * Gets the account keys
     *
     * @param string|null $methodId
     *
     * @return string[]
     */
    public function getAccountKeys(string $methodId = null): array
    {
        // Get the account keys for a method
        if ($methodId) {
            $publicKey        = $this->getValue('public_key', $methodId);
            $secretKey        = $this->getValue('secret_key', $methodId);
            $privateSharedKey = $this->getValue('private_shared_key', $methodId);
            if (!empty($publicKey) && !empty($secretKey) && !empty($privateSharedKey) && !$this->getValue(
                    'use_default_account',
                    $methodId
                )) {
                return [
                    'public_key'       => $publicKey,
                    'secretKey'        => $secretKey,
                    'privateSharedKey' => $privateSharedKey,
                ];
            }
        }

        // Return the default account keys
        return [
            'public_key'         => $this->getValue('public_key'),
            'secret_key'         => $this->getValue('secret_key'),
            'private_shared_key' => $this->getValue('private_shared_key'),
        ];
    }

    /**
     * Determines if 3DS should be enabled for a payment request
     *
     * @param string $methodId
     *
     * @return bool
     */
    public function needs3ds(string $methodId): bool
    {
        return (((bool) $this->getValue('three_ds', $methodId) === true)
                || ((bool) $this->getValue('mada_enabled', $methodId) === true));
    }

    /**
     * Checks and sets a capture time for the request.
     *
     * @return string
     */
    public function getCaptureTime(): string
    {
        // Get the capture time from config and covert from hours to seconds
        $captureTime = $this->getValue('capture_time');
        $captureTime *= 3600;

        // Force capture time to a minimum of 36 seconds
        $min         = $this->getValue('min_capture_time');
        $captureTime = $captureTime >= $min ? $captureTime : $min;

        // Check the setting
        if ($this->needsAutoCapture()) {
            // Calculate the capture date
            $captureDate = time() + $captureTime;

            return $this->utilities->formatDate($captureDate);
        }

        return '';
    }

    /**
     * Returns the store name.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreName(): string
    {
        $storeName = $this->getCoreValue('general/store_information/name');

        return !empty($storeName) ? trim($storeName) : $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Returns the store name.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Description getStoreLanguage function
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreLanguage(): string
    {
        $storeId = $this->storeManager->getStore()->getId();

        return $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the store code.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(): string
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * Description getStoreCountry function
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCountry(): string
    {
        $storeId = $this->storeManager->getStore()->getId();

        return $this->scopeConfig->getValue(
            'general/country/default',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Determines if the module is in production mode.
     *
     * @return bool
     */
    public function isLive(): bool
    {
        return $this->getValue('environment') == 1;
    }

    /**
     * Determines if the payment method needs auto capture.
     *
     * @return bool
     */
    public function needsAutoCapture(): bool
    {
        return ($this->getValue('payment_action') === 'authorize_capture' || (bool)$this->getValue(
                'mada_enabled',
                'checkoutcom_card_payment'
            ) === true);
    }

    /**
     * Determines if dynamic descriptors are available.
     *
     * @return bool
     */
    public function needsDynamicDescriptor(): bool
    {
        return $this->getValue('dynamic_descriptor_enabled') && !empty(
            $this->getValue(
                'descriptor_name'
            )
            ) && !empty($this->getValue('descriptor_city'));
    }

    /**
     * Get the MADA BIN file.
     *
     * @return string
     */
    public function getMadaBinFile(): string
    {
        return (int)$this->getValue('environment') === 1 ? $this->getValue('mada_test_file') : $this->getValue(
            'mada_live_file'
        );
    }

    /**
     * Gets the Alternative Payments.
     *
     * @return string[][]
     */
    public function getApms(): array
    {
        return $this->loader->loadApmList();
    }

    /**
     * Gets the module images path
     *
     * @return string
     */
    public function getImagesPath(): string
    {
        return $this->assetRepository->getUrl('CheckoutCom_Magento2::images');
    }

    /**
     * Gets the module CSS path
     *
     * @return string
     */
    public function getCssPath(): string
    {
        return $this->assetRepository->getUrl('CheckoutCom_Magento2::css');
    }

    /**
     * Determines if risk rules should be enabled for a payment request
     *
     * @param $methodId
     *
     * @return bool
     */
    public function needsRiskRules($methodId): bool
    {
        return (!$this->getValue('risk_rules_enabled', $methodId) === true);
    }
}
