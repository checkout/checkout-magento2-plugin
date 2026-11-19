<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
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

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Makes sure a Flow submit targets an order that is still awaiting payment and that was placed
 * for the submitted Flow session. Without it, the amount (taken from the last real order) and the
 * Flow session could belong to different orders.
 */
class FlowSubmitOrderGuard
{
    public function __construct(
        private readonly FlowPaymentAttemptService $attemptService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Each Flow placement creates a new order bound to the session used at that moment, so a
     * legitimate retry (new session or not) always submits on a freshly bound order.
     *
     * A client reference that differs from the order is only logged: it is ignored by the caller,
     * so tolerating it keeps older storefront scripts working without any security impact.
     *
     * @throws Exception
     */
    public function assertSubmittable(OrderInterface $order, string $sessionId, string $clientReference = ''): void
    {
        if ($order->getStatus() !== Order::STATE_PENDING_PAYMENT) {
            $this->logger->error(sprintf(
                '%s: order %s is not awaiting payment (status: %s)',
                __METHOD__,
                (string)$order->getIncrementId(),
                (string)$order->getStatus()
            ));

            throw new Exception(__('This order can no longer be paid. Please try again.')->render());
        }

        if ($this->attemptService->getOrderSessionId($order) !== $sessionId) {
            $this->logger->error(sprintf(
                '%s: Flow session %s is not bound to order %s',
                __METHOD__,
                $sessionId,
                (string)$order->getIncrementId()
            ));

            throw new Exception(__('The payment session does not match the order. Please try again.')->render());
        }

        if ($clientReference !== '' && $clientReference !== (string)$order->getIncrementId()) {
            $this->logger->warning(sprintf(
                '%s: client reference %s ignored, using order %s',
                __METHOD__,
                $clientReference,
                (string)$order->getIncrementId()
            ));
        }
    }
}
