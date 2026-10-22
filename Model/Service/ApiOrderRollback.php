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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Undo an order placed before its payment when the payment did not succeed
 */
class ApiOrderRollback
{
    public function __construct(
        private readonly Config $config,
        private readonly OrderHandlerService $orderHandler,
        private readonly OrderStatusHandlerService $orderStatusHandler,
        private readonly OrderManagementInterface $orderManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Registry $registry
    ) {
    }

    /**
     * Cancel or delete the order according to the payment processing configuration
     *
     * @param OrderInterface $order
     *
     * @return void
     * @throws LocalizedException
     */
    public function rollback(OrderInterface $order): void
    {
        // Handle order on failed payment if payment is order first
        if ($this->config->isPaymentWithOrderFirst()) {
            $this->orderStatusHandler->handleFailedPayment($order);
        }

        // Delete the order if payment is first
        $this->orderHandler->deleteOrder($order);
    }

    /**
     * Neutralize the order after an unexpected error, whatever the failed payment configuration:
     * the order is always cancelled, then deleted when the configuration asks for it
     *
     * @param OrderInterface $order
     *
     * @return bool False when the order could neither be cancelled nor deleted
     */
    public function rollbackOnError(OrderInterface $order): bool
    {
        $isCancelled = $order->getState() === Order::STATE_CANCELED
            || $this->orderManagement->cancel((int)$order->getEntityId());

        if ($this->config->isPaymentWithPaymentFirst()
            || $this->config->getValue('order_action_failed_payment') === 'delete'
        ) {
            $this->registry->register('isSecureArea', true);
            try {
                $this->orderRepository->delete($order);
            } finally {
                $this->registry->unregister('isSecureArea');
            }

            return true;
        }

        return $isCancelled;
    }

    /**
     * Order placed from the quote, used when an error prevented the order from being returned
     *
     * @param CartInterface|null $quote
     *
     * @return OrderInterface|null
     */
    public function findQuoteOrder(?CartInterface $quote): ?OrderInterface
    {
        if (!$quote || !$quote->getReservedOrderId()) {
            return null;
        }

        $order = $this->orderHandler->getOrder([
            'increment_id' => $quote->getReservedOrderId(),
            'quote_id' => $quote->getId(),
        ]);

        return $this->orderHandler->isOrder($order) ? $order : null;
    }
}
