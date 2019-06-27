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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\InstantPurchase;

/**
 * Class AvailabilityChecker
 */
class AvailabilityChecker implements \Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var VaultHandlerService
     */
    private $vaultHandler;

    /**
     * AvailabilityChecker constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        $this->config = $config;
        $this->vaultHandler = $vaultHandler;
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

        return $vaultEnabled
        && $instantPurchaseEnabled
        && $this->vaultHandler->userHasCards();
    }
}
