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

class ApplePaymentSettings extends AbstractSettingsProvider
{
    public const CONFIG_TITLE = 'payment/checkoutcom_apple_pay/title';
    public const CONFIG_IS_ACTIVE = 'payment/checkoutcom_apple_pay/active';
    public const CONFIG_ENABLED_ON_ALL_BROWSER = 'payment/checkoutcom_apple_pay/enabled_on_all_browsers';
    public const CONFIG_ENABLED_ON_CHECKOUT = 'payment/checkoutcom_apple_pay/enabled_on_checkout';
    public const CONFIG_ENABLED_ON_CART = 'payment/checkoutcom_apple_pay/enabled_on_cart';
    public const CONFIG_ENABLED_ON_MINICART = 'payment/checkoutcom_apple_pay/enabled_on_minicart';
    public const CONFIG_SORT_ORDER = 'payment/checkoutcom_apple_pay/sort_order';
    public const CONFIG_MERCHANT_ID = 'payment/checkoutcom_apple_pay/merchant_id';
    public const CONFIG_MERCHANT_ID_CERTIFICATE = 'payment/checkoutcom_apple_pay/merchant_id_certificate';
    public const CONFIG_PROCESSING_CERTIFICATE = 'payment/checkoutcom_apple_pay/processing_certificate';
    public const CONFIG_PROCESSING_CERTIFICATE_PASSWORD = 'payment/checkoutcom_apple_pay/processing_certificate_password';
    public const CONFIG_BUTTON_STYLE = 'payment/checkoutcom_apple_pay/button_style';
    public const CONFIG_SUPPORTED_NETWORK = 'payment/checkoutcom_apple_pay/supported_networks';
    public const CONFIG_MERCHANT_CAPABILITIES = 'payment/checkoutcom_apple_pay/merchant_capabilities';

}
