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

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Put an order under review when a webhook payment does not match it
 */
class WebhookMismatchHandlerService
{
    /**
     * Final order states, the order is left untouched but the mismatch is still recorded
     */
    private const FINAL_STATES = [Order::STATE_CANCELED, Order::STATE_CLOSED, Order::STATE_COMPLETE];

    /**
     * Transaction types recorded for a payment accepted by the gateway
     */
    private const PAYMENT_TRANSACTION_TYPES = [TransactionInterface::TYPE_AUTH, TransactionInterface::TYPE_CAPTURE];

    public function __construct(
        private readonly Config $config,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionHandlerService $transactionHandler,
        private readonly Logger $logger
    ) {
    }

    /**
     * Set the flagged status, add the mismatch details to the order history and log a critical error.
     * The order is never moved to processing. An order in a final state or already paid keeps its
     * status: anyone knowing its reference could otherwise block it with a payment of their own.
     *
     * @param OrderInterface|Order $order
     * @param string $reason
     *
     * @return void
     */
    public function flagOrder(OrderInterface $order, string $reason): void
    {
        $this->logger->critical(sprintf('Order %s: %s', $order->getIncrementId(), $reason));

        if (in_array($order->getState(), self::FINAL_STATES, true) || $this->isAlreadyPaid($order)) {
            $order->addCommentToStatusHistory($reason);
        } else {
            $flaggedStatus = (string)$this->config->getValue('order_status_flagged');
            $order->setStatus($flaggedStatus);
            $order->addCommentToStatusHistory($reason, $flaggedStatus);
        }

        $this->orderRepository->save($order);
    }

    /**
     * An order with an invoice, a paid amount or an authorization or capture transaction already has a verified payment
     *
     * @param OrderInterface|Order $order
     *
     * @return bool
     */
    private function isAlreadyPaid(OrderInterface $order): bool
    {
        return $order->hasInvoices()
            || (float)$order->getTotalPaid() > 0
            || $this->hasPaymentTransaction($order);
    }

    /**
     * Only an authorization or a capture proves a payment, a void or a refund transaction does not
     *
     * @param OrderInterface $order
     *
     * @return bool
     */
    private function hasPaymentTransaction(OrderInterface $order): bool
    {
        foreach ($this->transactionHandler->getTransactions($order->getId()) as $transaction) {
            if (in_array($transaction->getTxnType(), self::PAYMENT_TRANSACTION_TYPES, true)) {
                return true;
            }
        }

        return false;
    }
}
