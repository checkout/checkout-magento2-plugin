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

use CheckoutCom\Magento2\Helper\Utilities;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Check that an existing payment fetched by session id really belongs to the order being placed
 */
class ApiPaymentSessionGuard
{
    public function __construct(
        private readonly OrderHandlerService $orderHandler,
        private readonly Utilities $utilities
    ) {
    }

    /**
     * True when the payment reference, amount and currency all match the order
     *
     * @param mixed[] $payment
     * @param OrderInterface $order
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function matchesOrder(array $payment, OrderInterface $order): bool
    {
        if (!isset($payment['reference'], $payment['amount'], $payment['currency'])) {
            return false;
        }

        return (string)$payment['reference'] === (string)$order->getIncrementId()
            && (int)$payment['amount'] === $this->getExpectedAmount($order)
            && strtoupper((string)$payment['currency']) === strtoupper((string)$order->getOrderCurrencyCode());
    }

    /**
     * Order total in gateway minor units, computed like the payment requests: the total is first rounded to
     * 2 decimals, then converted with the currency multiplier (x1, x100, x1000). Converting the raw total instead
     * would reject the payments of x1000 currencies whose total has a third decimal.
     *
     * @param OrderInterface $order
     *
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getExpectedAmount(OrderInterface $order): int
    {
        return (int)round(
            (float)$this->orderHandler->amountToGateway(
                $this->utilities->formatDecimals((float)$order->getGrandTotal()),
                $order
            )
        );
    }
}
