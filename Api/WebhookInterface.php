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
     * AUTHENTICATION_EXPIRED constant
     */
    public const string AUTHENTICATION_EXPIRED = "authentication_expired";
    /**
     * PAYMENT_AUTHENTICATION_FAILED
     */
    public const string PAYMENT_AUTHENTICATION_FAILED = "payment_authentication_failed";
    /**
     * PAYMENT_CANCELLED constant
     */
    public const string PAYMENT_CANCELLED = "payment_cancelled";
    /**
     * PAYMENT_CAPTURE_DECLINED constant
     */
    public const string PAYMENT_CAPTURE_DECLINED = "payment_capture_declined";
    /**
     * PAYMENT_DECLINED constant
     */
    public const string PAYMENT_DECLINED = "payment_declined";
    /**
     * PAYMENT_EXPIRED constant
     */
    public const string PAYMENT_EXPIRED = "payment_expired";
    /**
     * PAYMENT_VOIDED constant
     */
    public const string PAYMENT_VOIDED = "payment_voided";
}
