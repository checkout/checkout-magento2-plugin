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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;

/**
 * Class AvailabilityChecker
 */
class AvailabilityChecker
{
    private Config $config;
    private VaultHandlerService $vaultHandler;

    public function __construct(
        Config $config,
        VaultHandlerService $vaultHandler
    ) {
        $this->config = $config;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Description isAvailable function
     *
     * @return bool
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

        $madaEnabled = $this->config->getValue('mada_enabled', 'checkoutcom_vault');

        return $vaultEnabled
               && $instantPurchaseEnabled
               && !$madaEnabled
               && $this->vaultHandler->userHasCards();
    }
}
