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
 * Interface WebhookEntityRepositoryInterface
 */
interface WebhookInterface
{
    const AUTHENTICATION_EXPIRED = "authentication_expired";
    const PAYMENT_AUTHENTICATION_FAILED = "payment_authentication_failed";
    const PAYMENT_CANCELLED = "payment_cancelled";
    const PAYMENT_CAPTURE_DECLINED = "payment_capture_declined";
    const PAYMENT_DECLINED = "payment_declined";
    const PAYMENT_EXPIRED = "payment_expired";
    const PAYMENT_VOIDED = "payment_voided";
}
