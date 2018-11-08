<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Payment\Gateway\Config\Config as BaseConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
    const KEY_SANDBOX_API_URL = 'sandbox_api_url';
    const KEY_LIVE_API_URL = 'live_api_url';
    const KEY_SANDBOX_EMBEDDED_URL = 'sandbox_embedded_url';
    const KEY_SANDBOX_HOSTED_URL = 'sandbox_hosted_url';
    const KEY_LIVE_EMBEDDED_URL = 'live_embedded_url';
    const KEY_LIVE_HOSTED_URL = 'live_hosted_url';
    const MIN_AUTO_CAPTURE_TIME = 0;
    const MAX_AUTO_CAPTURE_TIME = 168;
    const KEY_USE_DESCRIPTOR = 'descriptor_enable';
    const KEY_DESCRIPTOR_NAME = 'descriptor_name';
    const KEY_DESCRIPTOR_CITY = 'descriptor_city';
    const CODE_3DSECURE = 'three_d_secure';
    const KEY_THEME_COLOR = 'theme_color';
    const KEY_BUTTON_LABEL = 'button_label';
    const KEY_BOX_TITLE = 'box_title';
    const KEY_BOX_SUBTITLE = 'box_subtitle';
    const KEY_LOGO_URL = 'logo_url';
    const KEY_HOSTED_THEME = 'hosted_theme';
    const KEY_NEW_ORDER_STATUS = 'new_order_status';
    const KEY_ORDER_STATUS_AUTHORIZED = 'order_status_authorized';
    const KEY_ORDER_STATUS_CAPTURED = 'order_status_captured';
    const KEY_ORDER_STATUS_REFUNDED = 'order_status_refunded';
    const KEY_ORDER_STATUS_FLAGGED = 'order_status_flagged';
    const KEY_ACCEPTED_CURRENCIES = 'accepted_currencies';
    const KEY_PAYMENT_CURRENCY = 'payment_currency';
    const KEY_CUSTOM_CURRENCY = 'custom_currency';
    const KEY_PAYMENT_MODE = 'payment_mode';
    const KEY_AUTO_GENERATE_INVOICE = 'auto_generate_invoice';
    const KEY_EMBEDDED_THEME = 'embedded_theme';
    const KEY_EMBEDDED_CSS = 'embedded_css';
    const KEY_CUSTOM_CSS = 'custom_css';
    const KEY_CSS_FILE = 'css_file';
    const KEY_ORDER_COMMENTS_OVERRIDE = 'order_comments_override';
    const KEY_ORDER_CREATION = 'order_creation';
    const KEY_MADA_BINS_PATH = 'mada_bins_path';
    const KEY_MADA_BINS_PATH_TEST = 'mada_bins_path_test';
    const KEY_MADA_ENABLED = 'mada_enabled';

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
     * ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * StoreManagerInterface
     */
    protected $storeManager;

    /**
     * CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Config constructor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->scopeConfig  = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns the environment type.
     *
     * @return string
     */
    public function getEnvironment() {
        return (string) $this->getValue(
            self::KEY_ENVIRONMENT,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the vault option title.
     *
     * @return string
     */
    public function getVaultTitle() {
        return (string) $this->getValue(
            'payment/checkout_com_cc_vault/title',
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the vault card autosave state.
     *
     * @return bool
     */
    public function isCardAutosave() {
        return (bool) $this->getValue(
            'payment/checkout_com_cc_vault/autosave',
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the payment mode.
     *
     * @return string
     */
    public function getPaymentMode() {
        return (string) $this->getValue(
            self::KEY_PAYMENT_MODE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the automatic invoice generation state.
     *
     * @return bool
     */
    public function getAutoGenerateInvoice() {
        return (bool) $this->getValue(
            self::KEY_AUTO_GENERATE_INVOICE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the new order status.
     *
     * @return string
     */
    public function getNewOrderStatus() {
        return (string) $this->getValue(
            self::KEY_NEW_ORDER_STATUS,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the authorized order status.
     *
     * @return string
     */
    public function getOrderStatusAuthorized() {
        return (string) $this->getValue(
            self::KEY_ORDER_STATUS_AUTHORIZED,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the captured order status.
     *
     * @return string
     */
    public function getOrderStatusCaptured() {
        return (string) $this->getValue(
            self::KEY_ORDER_STATUS_CAPTURED,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the refunded order status.
     *
     * @return string
     */
    public function getOrderStatusRefunded() {
        return (string) $this->getValue(
            self::KEY_ORDER_STATUS_REFUNDED,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the flagged order status.
     *
     * @return string
     */
    public function getOrderStatusFlagged() {
        return (string) $this->getValue(
            self::KEY_ORDER_STATUS_FLAGGED,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the design settings.
     *
     * @return array
     */
    public function getDesignSettings() {
        return (array) array (
            'hosted' => array (
                'theme_color' => $this->getValue(
                    self::KEY_THEME_COLOR,
                    $this->storeManager->getStore()
                ),
                'button_label' => $this->getValue(
                    self::KEY_BUTTON_LABEL,
                    $this->storeManager->getStore()
                ),
                'box_title' => $this->getValue(
                    self::KEY_BOX_TITLE,
                    $this->storeManager->getStore()
                ),
                'box_subtitle' => $this->getValue(
                    self::KEY_BOX_SUBTITLE,
                    $this->storeManager->getStore()
                ),
                'logo_url' => $this->getLogoUrl()
            )
        );
    }

    /**
     * Returns the hosted logo URL.
     *
     * @return string
     */
    public function getLogoUrl() {
        $logoUrl = $this->getValue(
            self::KEY_LOGO_URL,
            $this->storeManager->getStore()
        );

        return (string) (isset($logoUrl) && !empty($logoUrl)) ? $logoUrl : 'none';
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
        return (string) $this->getValue(
            self::KEY_INTEGRATION,
            $this->storeManager->getStore()
        ); 
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
        if (!$this->getValue(
                self::KEY_ACTIVE,
                $this->storeManager->getStore()
            )) {
            return false;
        }

        $quote = $this->checkoutSession->getQuote();

        return (bool) in_array($quote->getQuoteCurrencyCode(), $this->getAcceptedCurrencies());
    }

    /**
     * Determines if Apple Pay is active.
     *
     * @return bool
     */
    public function isActiveApplePay() {
        if (!$this->getValue(
            'payment/checkout_com_applepay/active',
            $this->storeManager->getStore()
            )) {
            return false;
        }

        return true;  
    }

    /**
     * Gets the ApplePay processing certificate.
     *
     * @return string
     */
    public function getApplePayProcessingCertificate() {
        return (string) $this->getValue(
            'payment/checkout_com_applepay/processing_certificate',
            $this->storeManager->getStore()
        ); 
    }

    /**
     * Gets the ApplePay processing certificate password.
     *
     * @return string
     */
    public function getApplePayProcessingCertificatePassword() {
        return (string) $this->getValue(
            'payment/checkout_com_applepay/processing_certificate_password',
            $this->storeManager->getStore()
        ); 
    }

    /**
     * Gets the ApplePay merchant id certificate.
     *
     * @return string
     */
    public function getApplePayMerchantIdCertificate() {
        return (string) $this->getValue(
            'payment/checkout_com_applepay/merchant_id_certificate',
            $this->storeManager->getStore()
        ); 
    }

    /**
     * Gets the ApplePay merchant id.
     *
     * @return string
     */
    public function getApplePayMerchantId() {
        return (string) $this->getValue(
            'payment/checkout_com_applepay/merchant_id',
            $this->storeManager->getStore()
        ); 
    }

    /**
     * Gets the ApplePay debug mode.
     *
     * @return bool
     */
    public function getApplePayDebugMode() {
        return (bool) $this->getValue(
            'payment/checkout_com_applepay/debug',
            $this->storeManager->getStore()
        ); 
    }

    /**
     * Gets the ApplePay API mode.
     *
     * @return bool
     */
    public function getApplePayApiMode() {
        return (string) $this->getValue(
            'payment/checkout_com_applepay/api_mode',
            $this->storeManager->getStore()
        ); 
    }

    /**
     * Determines if the core order comments need override.
     *
     * @return bool
     */
    public function overrideOrderComments() {
        return (bool) $this->getValue(
            self::KEY_ORDER_COMMENTS_OVERRIDE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Determines if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugMode() {
        return (bool) $this->getValue(
            self::KEY_DEBUG,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the public key for client-side functionality.
     *
     * @return string
     */
    public function getPublicKey() {
        return (string) $this->getValue(
            self::KEY_PUBLIC_KEY,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the secret key for server-side functionality.
     *
     * @return string
     */
    public function getSecretKey() {
        return (string) $this->getValue(
            self::KEY_SECRET_KEY,
            $this->storeManager->getStore()
        );
    }

    /**
     * Is MADA BIN check enabled
     *
     * @return bool
     */
    public function isMadaEnabled() {
        return (bool) $this->getValue(
            self::KEY_MADA_ENABLED,
            $this->storeManager->getStore()
        );
    }

    /**
     * Return the MADA BINS file path.
     *
     * @return string
     */
    public function getMadaBinsPath() {
        return (string) (($this->isLive()) ?
        $this->getValue(
            self::KEY_MADA_BINS_PATH,
            $this->storeManager->getStore()
        ) : 
        $this->getValue(
            self::KEY_MADA_BINS_PATH_TEST,
            $this->storeManager->getStore()
        ));
    }

    /**
     * Returns the private shared key used for callback function.
     *
     * @return string
     */
    public function getPrivateSharedKey() {
        return (string) $this->getValue(
            self::KEY_PRIVATE_SHARED_KEY,
            $this->storeManager->getStore()
        );
    }

    /**
     * Determines if 3D Secure option is enabled.
     *
     * @return bool
     */
    public function isVerify3DSecure() {
        return (bool) $this->getValue(
            self::KEY_VERIFY_3DSECURE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Determines if attempt Non 3D Secure option is enabled.
     *
     * @return bool
     */
    public function isAttemptN3D() {
        return (bool) $this->getValue(
            self::KEY_ATTEMPT_N3D,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the currencies allowed for payment.
     *
     * @return array
     */
    public function getAcceptedCurrencies() {
        return (array) explode(',', $this->getValue(
            self::KEY_ACCEPTED_CURRENCIES,
            $this->storeManager->getStore()
        ));
    }

    /**
     * Returns the payment currency.
     *
     * @return string
     */
    public function getPaymentCurrency() {
        return (string) $this->getValue(
            self::KEY_PAYMENT_CURRENCY,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the custom payment currency.
     *
     * @return string
     */
    public function getCustomCurrency() {
        return (string) $this->getValue(
            self::KEY_CUSTOM_CURRENCY,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the API URL for sandbox environment.
     *
     * @return string
     */
    public function getSandboxApiUrl() {
        return (string) $this->getValue(
            self::KEY_SANDBOX_API_URL,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the API URL for sandbox environment.
     *
     * @return string
     */
    public function getLiveApiUrl() {
        return (string) $this->getValue(
            self::KEY_LIVE_API_URL,
            $this->storeManager->getStore()
        );
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
        return (string) $this->getValue(
            self::KEY_SANDBOX_HOSTED_URL,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the URL for hosted integration for live environment.
     *
     * @return string
     */
    public function getLiveHostedUrl() {
        return (string) $this->getValue(
            self::KEY_LIVE_HOSTED_URL,
            $this->storeManager->getStore()
        );
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
     * Returns the URL for embedded integration for sandbox environment.
     *
     * @return string
     */
    public function getSandboxEmbeddedUrl() {
        return (string) $this->getValue(
            self::KEY_SANDBOX_EMBEDDED_URL,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the URL for embedded integration for live environment.
     *
     * @return string
     */
    public function getLiveEmbeddedUrl() {
        return (string) $this->getValue(
            self::KEY_LIVE_EMBEDDED_URL,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the URL for embedded integration based on environment settings.
     *
     * @return string
     */
    public function getEmbeddedUrl() {
        return $this->isLive() ? $this->getLiveEmbeddedUrl() : $this->getSandboxEmbeddedUrl();
    }

    /**
     * Returns the CSS URL for embedded integration.
     *
     * @return string
     */
    public function getEmbeddedCss() {
        return (string) $this->getValue(
            self::KEY_EMBEDDED_CSS,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the CSS preference setting.
     *
     * @return string
     */
    public function getCssFile() {
        return (string) $this->getValue(
            self::KEY_CSS_FILE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the new order creation setting.
     *
     * @return string
     */
    public function getOrderCreation() {
        return (string) $this->getValue(
            self::KEY_ORDER_CREATION,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the custom CSS URL for embedded integration.
     *
     * @return string
     */
    public function getCustomCss() {
        // Prepare the paths
        $base_url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $file_path = $this->getValue(
            'payment/checkout_com/checkout_com_base_settings/custom_css',
            $this->storeManager->getStore()
        );

        return $base_url . 'checkout_com/' . $file_path;
    }

    /**
     * Determines if auto capture option is enabled.
     *
     * @return bool
     */
    public function isAutoCapture() {
        return (bool) $this->getValue(
            self::KEY_AUTO_CAPTURE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the number of hours, after which the capture method should be invoked.
     *
     * @return int
     */
    public function getAutoCaptureTimeInHours() {
        return $this->getValue(
            self::KEY_AUTO_CAPTURE_TIME,
            $this->storeManager->getStore()
        );
    }

    /**
     * Return the country specific card type config.
     *
     * @return array
     */
    public function getCountrySpecificCardTypeConfig() {
        $countriesCardTypes = unserialize($this->getValue(
            self::KEY_COUNTRY_CREDIT_CARD,
            $this->storeManager->getStore()
        ));

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
        $ccTypes = $this->getValue(
            self::KEY_CC_TYPES,
            $this->storeManager->getStore()
        );

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
        return (bool) $this->getValue(
            self::KEY_USE_CVV,
            $this->storeManager->getStore()
        );
    }

    /**
     * Check if the descriptor is enabled.
     *
     * @return bool
     */
    public function isDescriptorEnabled() {
        return (bool) $this->getValue(
            self::KEY_USE_DESCRIPTOR,
            $this->storeManager->getStore()
        );
    }
    /**
     * Returns the descriptor name.
     *
     * @return string
     */
    public function getDescriptorName() {
        return (string) $this->getValue(
            self::KEY_DESCRIPTOR_NAME,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the descriptor city.
     *
     * @return string
     */
    public function getDescriptorCity() {
        return (string) $this->getValue(
            self::KEY_DESCRIPTOR_CITY,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns the embedded theme.
     *
     * @return string
     */
    public function getEmbeddedTheme() {
        return (string) $this->getValue(
            self::KEY_EMBEDDED_THEME,
            $this->storeManager->getStore()
        );
    }
}
