<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;

class ConfigProvider implements ConfigProviderInterface {

    const CODE = 'checkout_com';

    const CC_VAULT_CODE = 'checkout_com_cc_vault';

    const THREE_DS_CODE = 'checkout_com_3ds';

    const CODE_APPLE_PAY = 'checkout_com_applepay';

    const CODE_GOOGLE_PAY = 'checkout_com_googlepay';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Config $config, Session $checkoutSession, StoreManagerInterface $storeManager) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig() {
        return [
            'payment' => [
                self::CODE => [
                    'isActive'                  => $this->config->isActive(),
                    'debug_mode'                => $this->config->isDebugMode(),
                    'public_key'                => $this->config->getPublicKey(),
                    'hosted_url'                => $this->config->getHostedUrl(),
                    'embedded_url'              => $this->config->getEmbeddedUrl(),
                    'countrySpecificCardTypes'  => $this->config->getCountrySpecificCardTypeConfig(),
                    'availableCardTypes'        => $this->config->getAvailableCardTypes(),
                    'useCvv'                    => $this->config->isCvvEnabled(),
                    'ccTypesMapper'             => $this->config->getCcTypesMapper(),
                    'ccVaultCode'               => self::CC_VAULT_CODE,
                    Config::CODE_3DSECURE       => [
                        'enabled' => $this->config->isVerify3DSecure(),
                    ],
                    'attemptN3D' => $this->config->isAttemptN3D(),
                    'integration'               => [
                        'type'          => $this->config->getIntegration(),
                        'isHosted'      => $this->config->isHostedIntegration(),
                    ],
                    'priceAdapter' => ChargeAmountAdapter::getConfigArray(),
                    'design_settings' => $this->config->getDesignSettings(),
                    'accepted_currencies' => $this->config->getAcceptedCurrencies(),
                    'payment_mode' => $this->config->getPaymentMode(),
                    'quote_value' => $this->getQuoteValue(),
                    'quote_currency' => $this->getQuoteCurrency(),
                    'embedded_theme' => $this->config->getEmbeddedTheme(),
                    'embedded_css' => $this->config->getEmbeddedCss(),
                    'css_file' => $this->config->getCssFile(),
                    'custom_css' => $this->config->getCustomCss(),
                    'vault_title' => $this->config->getVaultTitle(),
                    'order_creation' => $this->config->getOrderCreation(),
                    'card_autosave' => $this->config->isCardAutosave(),
                    'integration_language' => $this->config->getIntegrationLanguage()
                ],

                self::CODE_APPLE_PAY => [
                    'isActive' => $this->config->isActiveApplePay(),
                    'debugMode' => $this->config->getApplePayDebugMode(),
                    'processingCertificate' => $this->config->getApplePayProcessingCertificate(),
                    'processingCertificatePassword' => $this->config->getApplePayProcessingCertificatePassword(),
                    'merchantIdCertificate' => $this->config->getApplePayMerchantIdCertificate(),
                    'merchantId' => $this->config->getApplePayMerchantId(),
                    'buttonStyle' => $this->config->getApplePayButtonStyle(),
                    'storeName' => $this->config->getStoreName(),
                    'title' => $this->config->getApplePayTitle(),
                    'supportedNetworks' => $this->config->getApplePaySupportedNetworks(),
                    'merchantCapabilities' => $this->config->getApplePayMerchantCapabilities(),
                    'supportedCountries' => $this->config->getApplePaySupportedCountries()
                ],

                self::CODE_GOOGLE_PAY => [
                    'isActive' => $this->config->isActiveGooglePay(),
                    'debugMode' => $this->config->getGooglePayDebugMode(),
                    'title' => $this->config->getGooglePayTitle(),
                    'allowedNetworks' => $this->config->getGooglePayAllowedNetworks(),
                    'gatewayName' => $this->config->getGooglePayGatewayName(),
                    'merchantId' => $this->config->getGooglePayMerchantId(),
                    'environment' => $this->config->getGooglePayEnvironment(),
                    'buttonStyle' => $this->config->getGooglePayButtonStyle()
                ],
            ],
        ];
    }

    /**
     * Get a quote value.
     *
     * @return float
     */
    public function getQuoteValue() {
        // Return the quote amount
        $quote = $this->checkoutSession->getQuote()->collectTotals()->save();
        return $quote->getGrandTotal();
    }

    /**
     * Get a quote currency code.
     *
     * @return string
     */
    public function getQuoteCurrency() {
        // Return the quote currency
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Returns the success URL override.
     *
     * @return string
     */
    public function getSuccessUrl() {
        $url = $this->storeManager->getStore()->getBaseUrl() . 'checkout_com/payment/verify';
        return $url;
    }

    /**
     * Returns the fail URL override.
     *
     * @return string
     */
    public function getFailUrl() {
        $url = $this->storeManager->getStore()->getBaseUrl() . 'checkout_com/payment/fail';
        return $url;
    }
}