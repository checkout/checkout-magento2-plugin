<?php

/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 7
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Api;

/**
 * Interface ApplePayInterface
 */
interface ApplePayInterface
{
    public const FLOW_APPLE_PAY_IS_NATIVE_PARAM_NAME = 'flow_apple_pay_is_native';
    public const BROWSER_SUPPORTS_NATIVE_FLOW_APPLE_PAY = 'browser_supports_native_flow_apple_pay';
}
