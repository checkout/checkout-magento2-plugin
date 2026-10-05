<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Correlates a Flow payment attempt with the Magento order it belongs to.
 *
 * Two Flow attempts on the same quote can share the same reference (order increment id), so the
 * reference alone cannot tell which attempt a gateway event belongs to. A random token is sent in
 * the payment session metadata (Checkout.com echoes it back on the payment and on every webhook)
 * and stored on the order at placement, so a late event from a previous attempt can be detected
 * before it cancels or deletes the order of the current one.
 */
class FlowPaymentAttemptService
{
    public const ATTEMPT_ID_KEY = 'cko_attempt_id';

    public function generateAttemptId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function getOrderAttemptId(OrderInterface $order): ?string
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return null;
        }

        $attemptId = $payment->getAdditionalInformation(self::ATTEMPT_ID_KEY);

        return is_string($attemptId) && $attemptId !== '' ? $attemptId : null;
    }

    /**
     * Attempt token carried by a Checkout.com payment response or webhook payload data.
     */
    public function getPaymentAttemptId(array $payment): ?string
    {
        $attemptId = $payment['metadata'][self::ATTEMPT_ID_KEY] ?? null;

        return is_string($attemptId) && $attemptId !== '' ? $attemptId : null;
    }

    /**
     * True only when both the order and the payment carry a token and they differ. A missing
     * token on either side is treated as a match so orders and payments created before this
     * tracking existed keep being processed as before.
     */
    public function isFromAnotherAttempt(OrderInterface $order, array $payment): bool
    {
        $orderAttemptId = $this->getOrderAttemptId($order);
        $paymentAttemptId = $this->getPaymentAttemptId($payment);

        return $orderAttemptId !== null
            && $paymentAttemptId !== null
            && $orderAttemptId !== $paymentAttemptId;
    }
}
