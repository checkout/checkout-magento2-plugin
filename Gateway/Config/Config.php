<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Payment\Gateway\Config\Config as BaseConfig;
use CheckoutCom\Magento2\Model\Adminhtml\Source\Environment;
use CheckoutCom\Magento2\Model\Adminhtml\Source\Integration;

class Config extends BaseConfig {

    const KEY_ENVIRONMENT = 'environment';
    const KEY_ACTIVE = 'active';
    const KEY_DEBUG = 'debug';
    const KEY_CC_TYPES = 'cctypes';
    const KEY_USE_CVV = 'useccv';
    const KEY_COUNTRY_CREDIT_CARD = 'countrycreditcard';

    const KEY_INTEGRATION = 'integration';
    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_SECRET_KEY = 'secret_key';
    const KEY_PRIVATE_SHARED_KEY = 'private_shared_key';
    const KEY_AUTO_CAPTURE = 'auto_capture';
    const KEY_AUTO_CAPTURE_TIME = 'auto_capture_time';
    const KEY_VERIFY_3DSECURE = 'verify_3dsecure';

    const KEY_SANDBOX_SDK_URL = 'sandbox_sdk_url';
    const KEY_LIVE_SDK_URL = 'live_sdk_url';

    const KEY_SANDBOX_API_URL = 'sandbox_api_url';
    const KEY_LIVE_API_URL = 'live_api_url';

    const KEY_SANDBOX_HOSTED_URL = 'sandbox_hosted_url';
    const KEY_LIVE_HOSTED_URL = 'live_hosted_url';

    const MIN_AUTO_CAPTURE_TIME = 0;
    const MAX_AUTO_CAPTURE_TIME = 168;

    CONST KEY_USE_DESCRIPTOR = 'descriptor_enable';
    const KEY_DESCRIPTOR_NAME = 'descriptor_name';
    const KEY_DESCRIPTOR_CITY = 'descriptor_city';

    const CODE_3DSECURE = 'three_d_secure';

    /**
     * @var array
     */
    protected static $ccTypesMap = [
        'amex'          => 'AE',
        'visa'          => 'VI',
        'mastercard'    => 'MC',
        'discover'      => 'DI',
        'jcb'           => 'JCB',
        'diners'        => 'DN',
        'dinersclub'    => 'DN',
    ];

    /**
     * Returns the environment type.
     *
     * @return string
     */
    public function getEnvironment() {
        return (string) $this->getValue(self::KEY_ENVIRONMENT);
    }

    /**
     * Determines if the environment is set as sandbox mode.
     *
     * @return bool
     */
    public function isSandbox() {
        return $this->getEnvironment() === Environment::ENVIRONMENT_SANDBOX;
    }

    /**
     * Determines if the environment is set as live (production) mode.
     *
     * @return bool
     */
    public function isLive() {
        return $this->getEnvironment() === Environment::ENVIRONMENT_LIVE;
    }

    /**
     * Returns the type of integration.
     *
     * @return string
     */
    public function getIntegration() {
        return (string) $this->getValue(self::KEY_INTEGRATION);
    }

    /**
     * Determines if the gateway is configured to use widget integration.
     *
     * @return bool
     */
    public function isWidgetIntegration() {
        return $this->getIntegration() === Integration::INTEGRATION_WIDGET;
    }

    /**
     * Determines if the gateway is configured to use hosted integration.
     *
     * @return bool
     */
    public function isHostedIntegration() {
        return $this->getIntegration() === Integration::INTEGRATION_HOSTED;
    }

    /**
     * Determines if the gateway is active.
     *
     * @return bool
     */
    public function isActive() {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * Determines if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugMode() {
        return (bool) $this->getValue(self::KEY_DEBUG);
    }

    /**
     * Returns the public key for client-side functionality.
     *
     * @return string
     */
    public function getPublicKey() {
        return (string) $this->getValue(self::KEY_PUBLIC_KEY);
    }

    /**
     * Returns the secret key for server-side functionality.
     *
     * @return string
     */
    public function getSecretKey() {
        return (string) $this->getValue(self::KEY_SECRET_KEY);
    }

    /**
     * Returns the private shared key used for callback function.
     *
     * @return string
     */
    public function getPrivateSharedKey() {
        return (string) $this->getValue(self::KEY_PRIVATE_SHARED_KEY);
    }

    /**
     * Determines if 3D Secure option is enabled.
     *
     * @return bool
     */
    public function isVerify3DSecure() {
        return (bool) $this->getValue(self::KEY_VERIFY_3DSECURE);
    }

    /**
     * Returns the SDK URL for sandbox environment.
     *
     * @return string
     */
    public function getSandboxSdkUrl() {
        return (string) $this->getValue(self::KEY_SANDBOX_SDK_URL);
    }

    /**
     * Returns the SDK URL for live environment.
     *
     * @return string
     */
    public function getLiveSdkUrl() {
        return (string) $this->getValue(self::KEY_LIVE_SDK_URL);
    }

    /**
     * Returns the SDK URL based on environment settings.
     *
     * @return string
     */
    public function getSdkUrl() {
        return $this->isLive() ? $this->getLiveSdkUrl() : $this->getSandboxSdkUrl();
    }

    /**
     * Returns the API URL for sandbox environment.
     *
     * @return string
     */
    public function getSandboxApiUrl() {
        return (string) $this->getValue(self::KEY_SANDBOX_API_URL);
    }

    /**
     * Returns the API URL for sandbox environment.
     *
     * @return string
     */
    public function getLiveApiUrl() {
        return (string) $this->getValue(self::KEY_LIVE_API_URL);
    }

    /**
     * Returns the API URL based on environment settings.
     *
     * @return string
     */
    public function getApiUrl() {
        return $this->isLive() ? $this->getLiveApiUrl() : $this->getSandboxApiUrl();
    }

    /**
     * Returns the URL for hosted integration for sandbox environment.
     *
     * @return string
     */
    public function getSandboxHostedUrl() {
        return (string) $this->getValue(self::KEY_SANDBOX_HOSTED_URL);
    }

    /**
     * Returns the URL for hosted integration for live environment.
     *
     * @return string
     */
    public function getLiveHostedUrl() {
        return (string) $this->getValue(self::KEY_LIVE_HOSTED_URL);
    }

    /**
     * Returns the URL for hosted integration based on environment settings.
     *
     * @return string
     */
    public function getHostedUrl() {
        return $this->isLive() ? $this->getLiveHostedUrl() : $this->getSandboxHostedUrl();
    }

    /**
     * Determines if auto capture option is enabled.
     *
     * @return bool
     */
    public function isAutoCapture() {
        return (bool) $this->getValue(self::KEY_AUTO_CAPTURE);
    }

    /**
     * Returns the number of hours, after which the capture method should be invoked.
     *
     * @return int
     */
    public function getAutoCaptureTimeInHours() {
        $autoCaptureTime = (int) $this->getValue(self::KEY_AUTO_CAPTURE_TIME);

        return (int) max(min($autoCaptureTime, self::MAX_AUTO_CAPTURE_TIME), self::MIN_AUTO_CAPTURE_TIME);
    }

    /**
     * Return the country specific card type config.
     *
     * @return array
     */
    public function getCountrySpecificCardTypeConfig() {
        $countriesCardTypes = unserialize($this->getValue(self::KEY_COUNTRY_CREDIT_CARD));
        return is_array($countriesCardTypes) ? $countriesCardTypes : [];
    }

    /**
     * Get list of card types available for country.
     *
     * @param string $country
     * @return array
     */
    public function getCountryAvailableCardTypes($country) {
        $types = $this->getCountrySpecificCardTypeConfig();

        return (!empty($types[$country])) ? $types[$country] : [];
    }

    /**
     * Retrieve available credit card types.
     *
     * @return array
     */
    public function getAvailableCardTypes() {
        $ccTypes = $this->getValue(self::KEY_CC_TYPES);
        return ! empty($ccTypes) ? explode(',', $ccTypes) : [];
    }

    /**
     * Retrieve mapper between Magento and Checkout.com card types.
     *
     * @return array
     */
    public function getCcTypesMapper() {
        return self::$ccTypesMap;
    }

    /**
     * Check if CVV field is enabled.
     *
     * @return bool
     */
    public function isCvvEnabled() {
        return (bool) $this->getValue(self::KEY_USE_CVV);
    }

    /**
     * Check if the descriptor is enabled.
     *
     * @return bool
     */
    public function isDescriptorEnabled() {
        return (bool) $this->getValue(self::KEY_USE_DESCRIPTOR);
    }
    /**
     * Returns the descriptor name.
     *
     * @return string
     */
    public function getDescriptorName() {
        return (string) $this->getValue(self::KEY_DESCRIPTOR_NAME);
    }

    /**
     * Returns the descriptor city.
     *
     * @return string
     */
    public function getDescriptorCity() {
        return (string) $this->getValue(self::KEY_DESCRIPTOR_CITY);
    }

}
