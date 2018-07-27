<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */
 
namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use CheckoutCom\Magento2\Helper\Tools;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;
class Config {
    
    const KEY_MODTAG = 'modtag';
    const KEY_ENVIRONMENT = 'environment';
    const KEY_ENVIRONMENT_LIVE = 'live';
    const KEY_ACTIVE = 'active';
    const KEY_DEBUG = 'debug';
    const KEY_INTEGRATION = 'integration';
    const KEY_INTEGRATION_HOSTED = 'hosted';
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
    const KEY_ORDER_COMMENTS_OVERRIDE = 'order_comments_override';
    const KEY_ORDER_CREATION = 'order_creation';
    const KEY_EMBEDDED_CSS = 'embedded_css';

    /**
     * @var array
     */
    protected static $cardTypeMap = [
        'amex'          => 'AE',
        'visa'          => 'VI',
        'mastercard'    => 'MC',
        'discover'      => 'DI',
        'jcb'           => 'JCB',
        'diners'        => 'DN',
        'dinersclub'    => 'DN',
    ];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Config constructor.
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Tools $tools, CheckoutSession $checkoutSession, StoreManagerInterface $storeManager) {
        $this->scopeConfig = $scopeConfig;
        $this->tools = $tools;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve mapper between Magento and Checkout.com card types.
     *
     * @return array
     */
    public function getCardTypeMapper() {
        return self::$cardTypeMap;
    }

    /**
     * Get a payment token.
     *
     * @return string
     */

    private function getValue($path) {
        return $this->scopeConfig->getValue('payment/' . $this->tools->modmeta['tag'] . '/' . $path);
    }

    /**
     * Returns the environment type.
     *
     * @return string
     */
    public function getEnvironment() {
        return (string) $this->getValue(self::KEY_ENVIRONMENT);
    }

    /**
     * Returns the vault option title.
     *
     * @return string
     */
    public function getVaultTitle() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
        return (string) $scopeConfig->getValue('payment/checkout_com_cc_vault/title');
    }

    /**
     * Returns the vault card autosave state.
     *
     * @return bool
     */
    public function isCardAutosave() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
        return (bool) $scopeConfig->getValue('payment/checkout_com_cc_vault/autosave');
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
     * Returns the authorized order status.
     *
     * @return string
     */
    public function getOrderStatusAuthorized() {
        return (string) $this->getValue(self::KEY_ORDER_STATUS_AUTHORIZED);
    }

    /**
     * Returns the captured order status.
     *
     * @return string
     */
    public function getOrderStatusCaptured() {
        return (string) $this->getValue(self::KEY_ORDER_STATUS_CAPTURED);
    }

    /**
     * Returns the refunded order status.
     *
     * @return string
     */
    public function getOrderStatusRefunded() {
        return (string) $this->getValue(self::KEY_ORDER_STATUS_REFUNDED);
    }

    /**
     * Returns the flagged order status.
     *
     * @return string
     */
    public function getOrderStatusFlagged() {
        return (string) $this->getValue(self::KEY_ORDER_STATUS_FLAGGED);
    }

    /**
     * Returns the Hosted integration theme color
     *
     * @return string
     */
    public function getHostedThemeColor() {
        return $this->getValue(self::KEY_THEME_COLOR);
    }

    /**
     * Returns the Hosted integration button label
     *
     * @return string
     */
    public function getHostedButtonLabel() {
        return $this->getValue(self::KEY_BUTTON_LABEL);
    }

    /**
     * Returns the Hosted integration box title
     *
     * @return string
     */
    public function getHostedBoxTitle() {
        return $this->getValue(self::KEY_BOX_TITLE);
    }

    /**
     * Returns the Hosted integration box sub title
     *
     * @return string
     */
    public function getHostedBoxSubtitle() {
        return $this->getValue(self::KEY_BOX_SUBTITLE);
    }

    /**
     * Returns the Hosted integration logo URL
     *
     * @return string
     */
    public function getHostedLogoUrl() {
        return $this->getLogoUrl();
    }

    /**
     * Returns the hosted logo URL.
     *
     * @return string
     */
    public function getLogoUrl() {
        $logoUrl = $this->getValue(self::KEY_LOGO_URL);
        return (string) (isset($logoUrl) && !empty($logoUrl)) ? $logoUrl : 'none';
    }

    /**
     * Determines if the environment is set as live (production) mode.
     *
     * @return bool
     */
    public function isLive() {
        return $this->getEnvironment() == self::KEY_ENVIRONMENT_LIVE;
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
     * Determines if the gateway is active.
     *
     * @return bool
     */
    public function isActive() {
        if (!$this->getValue(self::KEY_ACTIVE)) {
            return false;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quote = $objectManager->create('Magento\Checkout\Model\Session')->getQuote();
        return (bool) in_array($quote->getQuoteCurrencyCode(), $this->getAcceptedCurrencies());
    }

    /**
     * Determines if the core order comments need override.
     *
     * @return bool
     */
    public function overrideOrderComments() {
        return (bool) $this->getValue(self::KEY_ORDER_COMMENTS_OVERRIDE);
    }

    /**
     * Get the quote value.
     *
     * @return bool
     */
    public function getQuoteValue() {
        return $this->checkoutSession->getQuote()->getGrandTotal()*100;
    }

    /**
     * Get a quote currency code.
     *
     * @return string
     */
    public function getQuoteCurrency() {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
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
     * Returns the custom payment currency.
     *
     * @return string
     */
    public function getCustomCurrency() {
        return (string) $this->getValue(self::KEY_CUSTOM_CURRENCY);
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
     * Returns the URL for embedded integration for sandbox environment.
     *
     * @return string
     */
    public function getSandboxEmbeddedUrl() {
        return (string) $this->getValue(self::KEY_SANDBOX_EMBEDDED_URL);
    }

    /**
     * Returns the URL for embedded integration for live environment.
     *
     * @return string
     */
    public function getLiveEmbeddedUrl() {
        return (string) $this->getValue(self::KEY_LIVE_EMBEDDED_URL);
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
        return (string) $this->getValue(self::KEY_EMBEDDED_CSS);
    }

    /**
     * Returns the new order creation setting.
     *
     * @return string
     */
    public function getOrderCreation() {
        return (string) $this->getValue(self::KEY_ORDER_CREATION);
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
        return $this->getValue(self::KEY_AUTO_CAPTURE_TIME);
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
}
