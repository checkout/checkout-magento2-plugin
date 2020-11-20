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

use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity\Collection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;

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
     * @var OrderStatusHandlerService
     */
    public $orderStatusHandler;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * @var WebhookEntityFactory
     */
    public $webhookEntityFactory;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var \CheckoutCom\Magento2\Gateway\Config\Config
     */
    public $config;

    /**
     * @var Collection
     */
    public $collection;

    /**
     * WebhookHandlerService constructor
     * @param \Magento\Sales\Model\Order $orderModel
     * @param OrderHandlerService $orderHandler
     * @param TransactionHandlerService $transactionHandler
     * @param \CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory $webhookEntityFactory
     * @param \CheckoutCom\Magento2\Gateway\Config\Config $config
     * @param \CheckoutCom\Magento2\Helper\Logger $logger
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService $orderStatusHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory $webhookEntityFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->transactionHandler = $transactionHandler;
        $this->webhookEntityFactory = $webhookEntityFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Process a single incoming webhook.
     * @param $order
     * @param $payload
     */
    public function processSingleWebhook($order, $payload)
    {
        if (isset($payload->data->action_id)) {
            // Store the order in a constant
            $this->order = $order;

            if (!$this->config->getValue('webhooks_table_enabled')) {
                $this->processWithoutSave($order, $payload);
            } else {
                $this->processWithSave($order, $payload);
            }

        } else {
            // Handle missing action ID
            $msg = __(
                'Missing action ID for webhook with payment ID %',
                $payload->data->id
            );
            $this->logger->write($msg);
        }
    }

    /**
     * Process all webhooks for an order.
     * @param $order
     */
    public function processAllWebhooks($order)
    {
        // Get the webhook entities
        $webhooks = $this->loadWebhookEntities([
            'order_id' => $order->getId()
        ]);

        $this->webhooksToProcess(
            $order,
            $webhooks
        );

        $this->setProcessedTime($webhooks);
    }

    public function processWithSave($order, $payload)
    {
        // Save the payload
        $this->saveWebhookEntity($payload, $order);

        // Get the saved webhook
        $webhooks = $this->loadWebhookEntities([
            'order_id' => $order->getId()
        ]);

        if ($this->hasAuth($webhooks, $payload)) {
            // Handle the order status for the webhook
            $this->webhooksToProcess(
                $order,
                $webhooks
            );
            $this->setProcessedTime($webhooks);
        }
    }

    public function processWithoutSave($order, $payload)
    {
        $webhooks = [];
        $webhook = [
                'event_id' => $payload->id,
                'event_type' => $payload->type,
                'event_data' => json_encode($payload),
                'action_id' => $payload->data->action_id,
                'payment_id' => $payload->data->id,
                'order_id' => $order->getId(),
                'processed' => false
                ];
        $webhooks[] = $webhook;
        
        // Handle the order status for the webhook
        $this->webhooksToProcess(
            $order,
            $webhooks
        );
    }

    /**
     * @param $order
     * @param array $webhooks
     * Generate transactions and set order status from webhooks.
     */
    public function webhooksToProcess($order, $webhooks = [])
    {
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
                if (!$webhook['processed']) {
                    $this->orderStatusHandler->setOrderStatus(
                        $order,
                        $webhook
                    );

                    $this->transactionHandler->handleTransaction(
                        $order,
                        $webhook
                    );
                }
            }
        }
    }

    /**
     * Load a webhook collection.
     * @param array $fields
     * @return array
     */
    public function loadWebhookEntities($fields = [])
    {
        // Create the collection
        $entities = $this->webhookEntityFactory->create();
        $this->collection = $entities->getCollection();

        // Add the field filters if needed
        if (!empty($fields)) {
            foreach ($fields as $key => $value) {
                $this->collection->addFieldToFilter($key, $value);
            }
        }

        $webhookEntitiesArr = $this->collection->getData();
        return $this->sortWebhooks($webhookEntitiesArr);
    }

    public function sortWebhooks($webhooks)
    {
        $sortedWebhooks = [];
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
                if ($webhook['event_type'] == 'payment_approved') {
                    array_unshift($sortedWebhooks, $webhook);
                } else {
                    array_push($sortedWebhooks, $webhook);
                }
            }
        }
        return $sortedWebhooks;
    }

    /**
     * Save the incoming webhook.
     * @param $payload
     * @param $order
     */
    public function saveWebhookEntity($payload, $order)
    {
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
            $entity->setReceivedTime();
            $entity->setData('processed', false);

            // Save the entity
            $entity->save();
        }
    }

    public function setProcessedTime($webhooks)
    {
        // Get a webhook entity instance
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
                if (!$webhook['processed']) {
                    $entity = $this->webhookEntityFactory->create();
                    $entity->load($webhook['id']);
                    $entity->setProcessed(true);
                    $entity->setProcessedTime();
                    $entity->save();
                }
            }
        }
    }

    /**
     * Delete a webhook by id.
     * @param $id
     */
    public function deleteWebhookEntity($id)
    {
        // Create the collection
        $entity = $this->webhookEntityFactory->create();
        $entity->load($id);
        $entity->delete();
    }

    public function hasAuth($webhooks, $payload)
    {
        if ($payload->type === 'payment_captured') {
            foreach ($webhooks as $webhook) {
                if ($webhook['event_type'] == 'payment_approved'
                    || $webhook['event_type'] == 'payment_capture_pending') {
                    
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Clean the webhooks table.
     */
    public function clean()
    {
        $webhooks = $this->loadWebhookEntities();

        foreach ($webhooks as $webhook) {
            $payload = json_decode($webhook['event_data'], true);
            $webhookDate = strtotime($payload['created_on']);
            $date = strtotime('-1 day');
            if ($webhookDate > $date && $webhook['processed']) {
                continue;
            }

            $this->deleteWebhookEntity($webhook['id']);
        }
    }
}
