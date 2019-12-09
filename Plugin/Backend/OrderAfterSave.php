<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Plugin\Backend;

use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderAfterSave.
 */
class OrderAfterSave
{
    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * AfterPlaceOrder constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler
    ) {
        $this->webhookHandler = $webhookHandler;
        $this->transactionHandler = $transactionHandler;
    }

    /**
     * Create transactions for the order.
     */
    public function afterSave(OrderRepositoryInterface $orderRepository, $order)
    {
        // Get the webhook entities
        $webhooks = $this->webhookHandler->loadEntities([
            'order_id' => $order->getId()
        ]);

        // Create the transactions
        $this->transactionHandler->webhookToTransaction(
            $order,
            $webhook
        );

        return $order;
    }
}