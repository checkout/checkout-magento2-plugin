<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Payment\Gateway\Config\Config as BaseConfig;
use Magento\Store\Model\StoreManagerInterface;
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
    const KEY_ATTEMPT_N3D = 'attemptN3D';

    const KEY_SANDBOX_HOSTED_SDK_URL = 'sandbox_hosted_sdk_url';
    const KEY_LIVE_HOSTED_SDK_URL = 'live_hosted_sdk_url';

    const KEY_SANDBOX_EMBEDDED_SDK_URL = 'sandbox_embedded_sdk_url';
    const KEY_LIVE_EMBEDDED_SDK_URL = 'live_embedded_sdk_url';

    const KEY_SANDBOX_HOSTED_API_URL = 'sandbox_hosted_api_url';
    const KEY_LIVE_HOSTED_API_URL = 'live_hosted_api_url';

    const KEY_SANDBOX_HOSTED_URL = 'sandbox_hosted_url';
    const KEY_LIVE_HOSTED_URL = 'live_hosted_url';

    const MIN_AUTO_CAPTURE_TIME = 0;
    const MAX_AUTO_CAPTURE_TIME = 168;

    CONST KEY_USE_DESCRIPTOR = 'descriptor_enable';
    const KEY_DESCRIPTOR_NAME = 'descriptor_name';
    const KEY_DESCRIPTOR_CITY = 'descriptor_city';

    const CODE_3DSECURE = 'three_d_secure';

    const KEY_THEME_COLOR = 'theme_color';
    const KEY_BUTTON_LABEL = 'button_label';

    const KEY_NEW_ORDER_STATUS = 'new_order_status';
    const KEY_ACCEPTED_CURRENCIES = 'accepted_currencies';
    const KEY_PAYMENT_CURRENCY = 'payment_currency';
    const KEY_PAYMENT_MODE = 'payment_mode';
    const KEY_AUTO_GENERATE_INVOICE = 'auto_generate_invoice';

    const KEY_EMBEDDED_THEME = 'embedded_theme';
    const KEY_EMBEDDED_CSS = 'embedded_css';

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
     * Returns the payment mode.
     *
     * @return string
     */
    public function getPaymentMode() {
        return (string) $this->getValue(self::KEY_PAYMENT_MODE);
    }

    /**
     * Returns the automatic invoice generation state.
     *
     * @return bool
     */
    public function getAutoGenerateInvoice() {
        return (bool) $this->getValue(self::KEY_AUTO_GENERATE_INVOICE);
    }

    /**
     * Returns the new order status.
     *
     * @return string
     */
    public function getNewOrderStatus() {
        return (string) $this->getValue(self::KEY_NEW_ORDER_STATUS);
    }

    /**
     * Returns the design settings.
     *
     * @return array
     */
    public function getDesignSettings() {
        return (array) array (
            'hosted' => array (
                'theme_color' => $this->getValue(self::KEY_THEME_COLOR),
                'button_label' => $this->getValue(self::KEY_BUTTON_LABEL)
            )
        );
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

       // Get an object manager instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        // Load the quote
        $quote = $objectManager->create('Magento\Checkout\Model\Session')->getQuote();     

        // Return the status
        return (bool) in_array($quote->getQuoteCurrencyCode(), $this->getAcceptedCurrencies());
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
     * Determines if attempt Non 3D Secure option is enabled.
     *
     * @return bool
     */
    public function isAttemptN3D() {
        return (bool) $this->getValue(self::KEY_ATTEMPT_N3D);
    }

    /**
     * Returns the currencies allowed for payment.
     *
     * @return array
     */
    public function getAcceptedCurrencies() {
        return (array) explode(',', $this->getValue(self::KEY_ACCEPTED_CURRENCIES));
    }

    /**
     * Returns the payment currency.
     *
     * @return string
     */
    public function getPaymentCurrency() {
        return (string) $this->getValue(self::KEY_PAYMENT_CURRENCY);
    }

    /**
     * Returns the SDK URL for sandbox environment.
     *
     * @return string
     */
    public function getSandboxSdkUrl() {
        return (string) $this->getValue(self::KEY_INTEGRATION) == 'hosted' ? $this->getValue(self::KEY_SANDBOX_HOSTED_SDK_URL) : $this->getValue(self::KEY_SANDBOX_EMBEDDED_SDK_URL);
    }

    /**
     * Returns the SDK URL for live environment.
     *
     * @return string
     */
    public function getLiveSdkUrl() {
        return (string) $this->getValue(self::KEY_INTEGRATION) == 'hosted' ? $this->getValue(self::KEY_LIVE_HOSTED_SDK_URL) : $this->getValue(self::KEY_LIVE_EMBEDDED_SDK_URL);
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
        return (string) $this->getValue(self::KEY_SANDBOX_HOSTED_API_URL);
    }

    /**
     * Returns the API URL for sandbox environment.
     *
     * @return string
     */
    public function getLiveApiUrl() {
        return (string) $this->getValue(self::KEY_LIVE_HOSTED_API_URL);
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
     * Returns the URL for embedded integration based on environment settings.
     *
     * @return string
     */
    public function getEmbeddedUrl() {
        return "";
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

    /**
     * Returns the embedded theme.
     *
     * @return string
     */
    public function getEmbeddedTheme() {
        return (string) $this->getValue(self::KEY_EMBEDDED_THEME);
    }

    /**
     * Returns the embedded custom CSS file.
     *
     * @return string
     */
    public function getEmbeddedCss() {
        return (string) $this->getValue(self::KEY_EMBEDDED_CSS);
    }   
}
