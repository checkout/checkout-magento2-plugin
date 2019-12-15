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

namespace CheckoutCom\Magento2\Model\Service;

/**
 * Class WebhookHandlerService.
 */
class WebhookHandlerService
{
    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * @var WebhookEntityFactory
     */
    public $webhookEntityFactory;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * WebhookHandlerService constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory $webhookEntityFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->orderHandler = $orderHandler;
        $this->transactionHandler = $transactionHandler;
        $this->webhookEntityFactory = $webhookEntityFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Process a single incoming webhook.
     */
    public function processSingleWebhook($order, $payload)
    {
        if (isset($payload->data->action_id)) {
            // Save the payload
            $this->saveEntity($payload);

            // Get the saved webhook
            $webhooks = $this->loadEntities([
                'order_id' => $order->getId(),
                'action_id' => $payload->data->action_id
            ]);

            // Handle transaction for the webhook
            $this->webhooksToTransactions(
                $order,
                $webhooks
            );
        }
        else {
            // Handle missing action ID
            $this->logger->write(
                __(
                    'Missing action ID for webhook with payment ID %',
                    $payload->data->id
                )
            );
        } 
    }

    /**
     * Process all webhooks for an order.
     */
    public function processAllWebhooks($order)
    {
        // Get the webhook entities
        $webhooks = $this->loadEntities([
            'order_id' => $order->getId()
        ]);

        // Create the transactions
        $this->webhooksToTransactions(
            $order,
            $webhooks
        );
    }

    /**
     * Generate transactions from webhooks.
     */
    public function webhooksToTransactions($order, $webhooks = [])
    {
        if (!empty($webhooks)) {
            // Prepare the webhooks deletion list
            $toDelete = [];

            // Create a transaction for each webhook
            foreach ($webhooks as $webhook) {
                // Handle the transaction
                $transaction = $this->transactionHandler
                ->handleTransaction(
                    $order,
                    $webhook
                );

                // Update the deletion list
                if ($transaction) {
                    $toDelete[] = $transaction;
                }
                else {
                    $toDelete[] = null;
                    $this->logger->write(
                        __(
                            'Failed to create a transaction for webhook with action ID %',
                            $webhook['action_id']
                        )
                    );
                }
            }

            // Delete the webhooks
            $this->deleteWebhooks($toDelete);
        }
    }

    /**
     * Delete webhooks from database.
     */
    public function deleteWebhooks($toDelete)
    {
        // Filter empty and null values
        $transactions = array_filter($toDelete);

        // Delete items if no empty or null values found
        if (count($transactions) == count($toDelete)) {
            foreach ($transactions as $transaction) {
                // Load the webhook entity
                $entity = $this->webhookEntityFactory
                ->create()
                ->load(
                    'action_id',
                    $transaction->getTxnId()
                );

                // Delete the webhook entity
                $entity->delete();
            }
        }
    }

    /**
     * Load a webhook collection.
     */
    public function loadEntities($fields = [])
    {
        // Create the collection
        $entities = $this->webhookEntityFactory->create();
        $collection = $entities->getCollection();

        // Add the field filters if needed
        if (!empty($fields)) {
            foreach ($fields as $key => $value) {
                $collection->addFieldToFilter($key, $value);
            }
        }

        return $collection->getData();
    }

    /**
     * Save the incoming webhook.
     */
    public function saveEntity($payload)
    {
        // Get the order id from the payload
        $order = $this->orderHandler->getOrder([
            'increment_id' => $payload->data->reference
        ]);

        // Save the webhook
        if ($this->orderHandler->isOrder($order)) {
            // Get a webhook entity instance
            $entity = $this->webhookEntityFactory->create();

            // Set the fields values
            $entity->setData('event_id', $payload->id);
            $entity->setData('event_type', $payload->type);
            $entity->setData(
                'event_data',
                json_encode($payload)
            );
            $entity->setData('action_id', $payload->data->action_id);
            $entity->setData('payment_id', $payload->data->id);
            $entity->setData('order_id', $order->getId());

            // Save the entity
            $entity->save();
        }
    }
}
