<?php

namespace CheckoutCom\Magento2\Model\InstantPurchase;

class AvailabilityChecker implements \Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface
{
    /**
     * @var Config
     */
    protected $config;


    /**
     * ConfigAlternativePayments  constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->configLoader = $config;
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool
    {
        // Get the vault state
        $vaultEnabled = $this->config->getValue(
            'active',
            'checkoutcom_vault'
        );

        // Get the instant purchase state
        $instantPurchaseEnabled = $this->config->getValue(
            'instant_purchase_enabled',
            'checkoutcom_vault'
        );

        return $vaultEnabled && $instantPurchaseEnabled;
    }
}