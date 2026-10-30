<?php

/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 8
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
    public const AUTHENTICATION_FAILED = "authentication_failed";
    public const PAYMENT_APPROVED = "payment_approved";
    public const PAYMENT_AUTHENTICATION_FAILED = "payment_authentication_failed";
    public const PAYMENT_CANCELED = "payment_canceled";
    /**
     * @deprecated Checkout.com sends "payment_canceled", use PAYMENT_CANCELED instead
     */
    public const PAYMENT_CANCELLED = self::PAYMENT_CANCELED;
    public const PAYMENT_CAPTURE_DECLINED = "payment_capture_declined";
    public const PAYMENT_CAPTURE_PENDING = "payment_capture_pending";
    public const PAYMENT_CAPTURED = "payment_captured";
    public const PAYMENT_DECLINED = "payment_declined";
    public const PAYMENT_EXPIRED = "payment_expired";
    public const PAYMENT_PENDING = "payment_pending";
    public const PAYMENT_REFUND_DECLINED = "payment_refund_declined";
    public const PAYMENT_REFUNDED = "payment_refunded";
    public const PAYMENT_VOID_DECLINED = "payment_void_declined";
    public const PAYMENT_VOIDED = "payment_voided";

    /**
     * Webhook types processed by the module, other types are acknowledged and ignored
     */
    public const SUPPORTED_EVENTS = [
        self::AUTHENTICATION_EXPIRED,
        self::AUTHENTICATION_FAILED,
        self::PAYMENT_APPROVED,
        self::PAYMENT_AUTHENTICATION_FAILED,
        self::PAYMENT_CANCELED,
        self::PAYMENT_CAPTURE_DECLINED,
        self::PAYMENT_CAPTURE_PENDING,
        self::PAYMENT_CAPTURED,
        self::PAYMENT_DECLINED,
        self::PAYMENT_EXPIRED,
        self::PAYMENT_PENDING,
        self::PAYMENT_REFUND_DECLINED,
        self::PAYMENT_REFUNDED,
        self::PAYMENT_VOID_DECLINED,
        self::PAYMENT_VOIDED,
    ];

    /**
     * Webhook types of a failed payment, the order is canceled or deleted depending on the configuration
     */
    public const FAILED_PAYMENT_EVENTS = [
        self::PAYMENT_DECLINED,
        self::PAYMENT_EXPIRED,
        self::PAYMENT_CANCELED,
        self::PAYMENT_VOIDED,
        self::PAYMENT_CAPTURE_DECLINED,
        self::PAYMENT_AUTHENTICATION_FAILED,
    ];
}
