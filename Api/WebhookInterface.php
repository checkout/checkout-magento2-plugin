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
    /**
     * Webhook type constant
     */
    public const AUTHENTICATION_EXPIRED = "authentication_expired";
    public const PAYMENT_AUTHENTICATION_FAILED = "payment_authentication_failed";
    public const PAYMENT_CANCELLED = "payment_cancelled";
    public const PAYMENT_CAPTURE_DECLINED = "payment_capture_declined";
    public const PAYMENT_DECLINED = "payment_declined";
    public const PAYMENT_EXPIRED = "payment_expired";
    public const PAYMENT_VOIDED = "payment_voided";
}
