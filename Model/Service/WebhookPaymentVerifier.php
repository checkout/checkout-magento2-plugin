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

use CheckoutCom\Magento2\Api\WebhookInterface;
use CheckoutCom\Magento2\Exception\WebhookPaymentMismatchException;
use CheckoutCom\Magento2\Exception\WebhookStatusMismatchException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Check a webhook against the payment fetched from the Checkout.com API before it can change an order.
 *
 * The webhook body is never trusted: the order match (reference, amount, currency), the payment status and
 * the cumulative balances all come from the API response.
 */
class WebhookPaymentVerifier
{
    /**
     * API payment statuses compatible with each controlled webhook type (lowercase)
     */
    private const AUTHORIZED_STATUSES = [
        'authorized',
        'partially captured',
        'captured',
        'partially refunded',
        'refunded',
        'voided',
        'paid',
    ];
    private const CAPTURED_STATUSES = ['captured', 'partially captured', 'partially refunded', 'refunded', 'paid'];
    private const REFUNDED_STATUSES = ['partially refunded', 'refunded'];
    private const VOIDED_STATUSES = ['voided', 'partially captured', 'captured'];

    public function __construct(
        private readonly ApiPaymentSessionGuard $paymentSessionGuard,
        private readonly OrderHandlerService $orderHandler
    ) {
    }

    /**
     * Verify the webhook against the API payment and return the verified cumulative figures.
     *
     * Returns an empty array for webhook types that neither invoice nor refund nor change the payment state.
     * Amounts are in gateway minor units.
     *
     * @param string $eventType
     * @param mixed[] $payment API payment details
     * @param OrderInterface $order
     *
     * @return array{status: string, amount: int, total_captured: int, total_refunded: int, total_voided: int}|array{}
     * @throws WebhookPaymentMismatchException The payment does not belong to the order
     * @throws WebhookStatusMismatchException The event contradicts the payment status
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function verify(string $eventType, array $payment, OrderInterface $order): array
    {
        $allowedStatuses = $this->getAllowedStatuses($eventType);
        if ($allowedStatuses === []) {
            return [];
        }

        $this->assertMatchesOrder($eventType, $payment, $order);

        $status = strtolower(trim((string)($payment['status'] ?? '')));
        $balances = is_array($payment['balances'] ?? null) ? $payment['balances'] : [];
        $amount = (int)$payment['amount'];

        $totalCaptured = $this->getTotalCaptured($payment, $status, $balances);
        $totalRefunded = (int)($balances['total_refunded'] ?? 0);
        $totalVoided = (int)($balances['total_voided'] ?? 0);

        if (!in_array($status, $allowedStatuses, true)) {
            throw new WebhookStatusMismatchException(
                __(
                    'Webhook %1 for order %2 contradicts the API payment %3 status "%4".',
                    $eventType,
                    $order->getIncrementId(),
                    (string)($payment['id'] ?? ''),
                    (string)($payment['status'] ?? '')
                )
            );
        }

        $this->assertBalancesMatchEvent($eventType, $payment, $order, $amount, $totalCaptured, $totalRefunded, $totalVoided);

        return [
            'status' => $status,
            'amount' => $amount,
            'total_captured' => $totalCaptured,
            'total_refunded' => $totalRefunded,
            'total_voided' => $totalVoided,
        ];
    }

    /**
     * API statuses accepted for a webhook type, empty when the type is not controlled
     *
     * @param string $eventType
     *
     * @return string[]
     */
    private function getAllowedStatuses(string $eventType): array
    {
        return match ($eventType) {
            WebhookInterface::PAYMENT_APPROVED => self::AUTHORIZED_STATUSES,
            WebhookInterface::PAYMENT_CAPTURED => self::CAPTURED_STATUSES,
            WebhookInterface::PAYMENT_REFUNDED => self::REFUNDED_STATUSES,
            WebhookInterface::PAYMENT_VOIDED => self::VOIDED_STATUSES,
            default => [],
        };
    }

    /**
     * Reference, amount and currency of the payment must be the ones of the order
     *
     * @param string $eventType
     * @param mixed[] $payment
     * @param OrderInterface $order
     *
     * @return void
     * @throws WebhookPaymentMismatchException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function assertMatchesOrder(string $eventType, array $payment, OrderInterface $order): void
    {
        if ($this->paymentSessionGuard->matchesOrder($payment, $order)) {
            return;
        }

        $expectedAmount = (int)round(
            (float)$this->orderHandler->amountToGateway((float)$order->getGrandTotal(), $order)
        );

        throw new WebhookPaymentMismatchException(
            __(
                'Webhook %1 rejected: Checkout.com payment %2 does not match order %3. '
                . 'Order total: %4 %5 (%6 in gateway minor units), payment amount: %7 %8 in gateway minor units, '
                . 'payment reference: %9.',
                $eventType,
                (string)($payment['id'] ?? ''),
                $order->getIncrementId(),
                (string)$order->getGrandTotal(),
                (string)$order->getOrderCurrencyCode(),
                $expectedAmount,
                (string)($payment['amount'] ?? 'n/a'),
                (string)($payment['currency'] ?? 'n/a'),
                (string)($payment['reference'] ?? 'n/a')
            )
        );
    }

    /**
     * Cumulative captured amount returned by the API, in minor units.
     * Payments without balances fall back on the payment amount once captured or paid.
     *
     * @param mixed[] $payment
     * @param string $status
     * @param mixed[] $balances
     *
     * @return int
     */
    private function getTotalCaptured(array $payment, string $status, array $balances): int
    {
        if (isset($balances['total_captured'])) {
            return (int)$balances['total_captured'];
        }

        return in_array($status, ['captured', 'paid'], true) ? (int)$payment['amount'] : 0;
    }

    /**
     * The cumulative balances must prove that the action announced by the webhook really happened
     *
     * @param string $eventType
     * @param mixed[] $payment
     * @param OrderInterface $order
     * @param int $amount
     * @param int $totalCaptured
     * @param int $totalRefunded
     * @param int $totalVoided
     *
     * @return void
     * @throws WebhookStatusMismatchException
     */
    private function assertBalancesMatchEvent(
        string $eventType,
        array $payment,
        OrderInterface $order,
        int $amount,
        int $totalCaptured,
        int $totalRefunded,
        int $totalVoided
    ): void {
        $consistent = match ($eventType) {
            WebhookInterface::PAYMENT_CAPTURED => $totalCaptured > 0 && $totalCaptured <= $amount,
            WebhookInterface::PAYMENT_REFUNDED => $totalRefunded > 0 && $totalRefunded <= $totalCaptured,
            WebhookInterface::PAYMENT_VOIDED => $totalVoided > 0
                || strtolower(trim((string)($payment['status'] ?? ''))) === 'voided',
            default => true,
        };

        if (!$consistent) {
            throw new WebhookStatusMismatchException(
                __(
                    'Webhook %1 for order %2 contradicts the API payment %3 balances '
                    . '(captured: %4, refunded: %5, voided: %6, amount: %7).',
                    $eventType,
                    $order->getIncrementId(),
                    (string)($payment['id'] ?? ''),
                    $totalCaptured,
                    $totalRefunded,
                    $totalVoided,
                    $amount
                )
            );
        }
    }
}
