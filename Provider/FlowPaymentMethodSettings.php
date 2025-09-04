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

namespace CheckoutCom\Magento2\Provider;

use CheckoutCom\Magento2\Gateway\Config\Loader;
use Magento\Framework\App\Config\ScopeConfigInterface;

class FlowPaymentMethodSettings extends AbstractSettingsProvider
{
    public const CONFIG_FLOW_PAYMENT_APM_METHODS = 'payment/checkoutcom_apm/active';
    public const CONFIG_FLOW_PAYMENT_APM_METHODS_LIST = 'payment/checkoutcom_apm/apm_flow_enabled';
    public const CONFIG_FLOW_PAYMENT_KLARNA_METHODS = 'payment/checkoutcom_klarna/active';
    public const CONFIG_FLOW_PAYMENT_GPAY_METHODS = 'payment/checkoutcom_google_pay/active';
    public const CONFIG_FLOW_PAYMENT_APPLEPAY_METHODS = 'payment/checkoutcom_apple_pay/active';
    public const CONFIG_FLOW_PAYMENT_PAYPAL_METHODS = 'payment/checkoutcom_paypal/active';
    public const CONFIG_FLOW_PAYMENT_CARD_METHODS = 'payment/checkoutcom_card_payment/active';

    private const METHOD_CARD_NAME = 'card';
    private const METHOD_KLARNA_NAME = 'klarna';
    private const METHOD_GOOGLEPAY_NAME = 'googlepay';
    private const METHOD_APPLEPAY_NAME = 'applepay';
    private const METHOD_PAYPAL_NAME = 'paypal';

    private array $pm = [];
    private array $apm = [];

    private Loader $configLoader;

    public function __construct(
        Loader $configLoader,
        ScopeConfigInterface $scopeConfig,
    ) {
        parent::__construct(
            $scopeConfig
        );
        $this->configLoader = $configLoader;
    }

    public function isKlarnaEnabled(?string $website): bool
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_KLARNA_METHODS,
            $website
        ) === "1";
    }

    public function isGooglePayEnabled(?string $website): bool
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_GPAY_METHODS,
            $website
        ) === "1";
    }

    public function isApplePayEnabled(?string $website): bool
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_APPLEPAY_METHODS,
            $website
        ) === "1";
    }

    public function isPaypalEnabled(?string $website): bool
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_PAYPAL_METHODS,
            $website
        ) === "1";
    }

    public function isCardPaymentEnabled(?string $website): bool
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_CARD_METHODS,
            $website
        ) === "1";
    }

    public function isApmEnabled(?string $website): bool
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_APM_METHODS,
            $website
        ) === "1";
    }

    public function getSelectedApmMethods(?string $websiteCode, bool $skipActivationCheck = false): array
    {
        if(!$this->isApmEnabled($websiteCode) && !$skipActivationCheck ) {
            return [];
        }

        $configuration = (string)$this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_APM_METHODS_LIST,
            $websiteCode,
        );
        
        if (empty($configuration)) {
            return [];
        }

        return explode(',', $configuration);
    }

    public function getEnabledPaymentMethods(string $websiteCode): array
    {
        $enabledPaymentMethods = [];

        if ($this->isCardPaymentEnabled($websiteCode)) {
            $enabledPaymentMethods[] = self::METHOD_CARD_NAME;
        }
        if ($this->isKlarnaEnabled($websiteCode)) {
            $enabledPaymentMethods[] = self::METHOD_KLARNA_NAME;
        }
        if ($this->isGooglePayEnabled($websiteCode)) {
            $enabledPaymentMethods[] = self::METHOD_GOOGLEPAY_NAME;
        }
        if ($this->isApplePayEnabled($websiteCode)) {
            $enabledPaymentMethods[] = self::METHOD_APPLEPAY_NAME;
        }
        if ($this->isPaypalEnabled($websiteCode)) {
            $enabledPaymentMethods[] = self::METHOD_PAYPAL_NAME;
        }

        return array_merge($enabledPaymentMethods, $this->getSelectedApmMethods($websiteCode));
    }

    public function getAllPaymentMethods(): array
    {
        if(empty($this->pm)) {
            $this->loadPaymentMethods();
        }

        if(empty($this->apm)) {
            $this->loadAlternativePaymentMethods();
        }

        return array_merge($this->pm, $this->apm);
    }

    private function loadPaymentMethods(): void
    {
        $pm = [
            self::METHOD_CARD_NAME => [
                'id' => self::METHOD_CARD_NAME,
            ],
            self::METHOD_KLARNA_NAME => [
                'id' => self::METHOD_KLARNA_NAME,
                'currencies' => 'AUD,CHF,CZK,DKK,EUR,GBP,NOK,PLN,SEK,USD',
                'countries' => 'AT,AU,BE,CH,CZ,DE,DK,ES,FR,FI,GB,GR,IE,IT,NL,NO,PL,PT,SE,US'
            ],
            self::METHOD_GOOGLEPAY_NAME => [
                'id' => self::METHOD_GOOGLEPAY_NAME,
            ],
            self::METHOD_APPLEPAY_NAME => [
                'id' => self::METHOD_APPLEPAY_NAME,
            ],
            self::METHOD_PAYPAL_NAME => [
                'id' => self::METHOD_PAYPAL_NAME,
            ]
        ];

        $this->pm = $pm;
    }

    private function loadAlternativePaymentMethods(): void
    {
        $apmList = $this->configLoader->loadApmList(Loader::APM_FLOW_FILE_NAME);

        $apmMethods = [];

        foreach ($apmList as $apm) {
            $apmMethods[$apm['value']] = $apm;
        }

        $this->apm = $apmMethods;
    }
}
