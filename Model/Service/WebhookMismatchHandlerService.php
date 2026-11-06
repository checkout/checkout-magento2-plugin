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

    public function __construct(
        private readonly Config $config,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Logger $logger
    ) {
    }

    /**
     * Set the flagged status, add the mismatch details to the order history and log a critical error.
     * The order is never moved to processing.
     *
     * @param OrderInterface|Order $order
     * @param string $reason
     *
     * @return void
     */
    public function flagOrder(OrderInterface $order, string $reason): void
    {
        $this->logger->critical(sprintf('Order %s: %s', $order->getIncrementId(), $reason));

        if (in_array($order->getState(), self::FINAL_STATES, true)) {
            $order->addCommentToStatusHistory($reason);
        } else {
            $flaggedStatus = (string)$this->config->getValue('order_status_flagged');
            $order->setStatus($flaggedStatus);
            $order->addCommentToStatusHistory($reason, $flaggedStatus);
        }

        $this->orderRepository->save($order);
    }
}
