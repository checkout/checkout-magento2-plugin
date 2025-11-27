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

namespace CheckoutCom\Magento2\Provider;

use CheckoutCom\Magento2\Provider\AbstractSettingsProvider;

class OrderSettings extends AbstractSettingsProvider {



    public const CONFIG_ACTION_FAILED_PAYMENT = 'settings/checkoutcom_configuration/order_action_failed_payment';
    public const CONFIG_ORDER_EMAIL = 'settings/checkoutcom_configuration/order_email';
    public const CONFIG_STATUS_AUTHORIZED = 'settings/checkoutcom_configuration/order_status_authorized';
    public const CONFIG_STATUS_CAPTURED = 'settings/checkoutcom_configuration/order_status_captured';
    public const CONFIG_STATUS_VOIDED = 'settings/checkoutcom_configuration/order_status_voided';
    public const CONFIG_STATUS_REFUNDED = 'settings/checkoutcom_configuration/order_status_refunded';
    public const CONFIG_STATUS_FLAGGED = 'settings/checkoutcom_configuration/order_status_flagged';

    public function getActionOnFailedPayment(?string $website): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_ACTION_FAILED_PAYMENT,
            $website
        );
    }
}
