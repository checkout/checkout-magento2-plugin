<?php

namespace CheckoutCom\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;

class ConfigProvider implements ConfigProviderInterface {

    const CODE = 'checkout_com';

    const CC_VAULT_CODE = 'checkout_com_cc_vault';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param PaymentTokenService $paymentTokenService
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Config $config, PaymentTokenService $paymentTokenService, Session $checkoutSession, StoreManagerInterface $storeManager) {
        $this->config = $config;
        $this->paymentTokenService  = $paymentTokenService;
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
                    'payment_currency' => $this->config->getPaymentCurrency(),
                    'payment_mode' => $this->config->getPaymentMode(),
                    'payment_token' => $this->getPaymentToken(),
                    'quote_value' => $this->getQuoteValue(),
                    'quote_currency' => $this->getQuoteCurrency(),
                    'quote_currency' => $this->getQuoteCurrency(),
                    'embedded_theme' => $this->config->getEmbeddedTheme(),
                    'embedded_css' => $this->config->getEmbeddedCss(),
                    'custom_css' => $this->config->getCustomCss(),
                ],
            ],
        ];
    }

    /**
     * Get a payment token.
     *
     * @return string
     */
    public function getPaymentToken() {
        return $this->paymentTokenService->getToken();
    }

    /**
     * Get a quote value.
     *
     * @return float
     */
    public function getQuoteValue() {

        // Get the quote amount
        $amount =  ChargeAmountAdapter::getPaymentFinalCurrencyValue($this->checkoutSession->getQuote()->getGrandTotal());

        // Get the quote currency
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        // Prepare the amount 
        $value = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);

        return $value;
    }
   
    /**
     * Get a quote currency code.
     *
     * @return string
     */
    public function getQuoteCurrency() {

        // Get the quote currency
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        // Return the quote currency
        return ChargeAmountAdapter::getPaymentFinalCurrencyCode($currencyCode);
    }
}
