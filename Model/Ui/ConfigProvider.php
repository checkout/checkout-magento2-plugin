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
        $isActive = $this->config->isActive();

        return [
            'payment' => [
                self::CODE => [
                    'isActive'                  => $isActive,
                    'debug_mode'                => $this->config->isDebugMode(),
                    'public_key'                => $this->config->getPublicKey(),
                    'hosted_url'                => $this->config->getHostedUrl(),
                    'embedded_url'              => $this->config->getEmbeddedUrl(),
                    'countrySpecificCardTypes'  => $this->config->getCountrySpecificCardTypeConfig(),
                    'availableCardTypes'        => $this->config->getAvailableCardTypes(),
                    'useCvv'                    => $this->config->isCvvEnabled(),
                    'ccTypesMapper'             => $this->config->getCcTypesMapper(),
                    'ccVaultCode'               => self::CC_VAULT_CODE,
                    'successUrl'                => $this->getSuccessUrl(),
                    'failUrl'                   => $this->getFailUrl(),
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
        return $this->checkoutSession->getQuote()->getGrandTotal()*100;
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
        return $this->storeManager->getStore()->getUrl('routepath', ['_current' => true]);
    }

    /**
     * Returns the fail URL override.
     *
     * @return string
     */
    public function getFailUrl() {
        return $this->storeManager->getStore()->getUrl('routepath', ['_current' => true]);
    }
}
