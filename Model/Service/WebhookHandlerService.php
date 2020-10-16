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

use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class WebhookHandlerService.
 */
class WebhookHandlerService
{
    /**
     * @var orderModel
     */
    public $orderModel;

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
     * @var Config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;
    
    public $order;

    /**
     * WebhookHandlerService constructor
     */
    public function __construct(
        \Magento\Sales\Model\Order $orderModel,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService $orderStatusHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory $webhookEntityFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->orderModel = $orderModel;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
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
            // store the order in a class 
            $this->order = $order;
            
            // Save the payload
            $this->saveEntity($payload, $order);

            // Get the saved webhook
            $webhooks = $this->loadEntities([
                'order_id' => $this->order->getId(),
                'action_id' => $payload->data->action_id
            ]);

            // Check if order is on hold
            if ($order->getState() != 'holded') {
                // Handle the order status for the webhook
                $this->webhooksToProcess(
                    $order,
                    $webhooks
                );
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
     */
    public function processAllWebhooks($order)
    {
        // Get the webhook entities
        $webhooks = $this->loadEntities([
            'order_id' => $order->getId()
        ]);

        $this->webhooksToProcess(
            $order,
            $webhooks
        );
    }

    /**
     * Generate transactions and set order status from webhooks.
     */
    public function webhooksToProcess($order, $webhooks = [])
    {
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
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
    public function saveEntity($payload, $order)
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

            // Save the entity
            $entity->save();
        }
    }

    /**
     * Delete a webhook by id.
     */
    public function deleteEntity($id)
    {
        // Create the collection
        $entity = $this->webhookEntityFactory->create();
        $entity->load($id);
        $entity->delete();
    }

    /**
     * Clean the webhooks table.
     */
    public function clean()
    {
        $webhooks = $this->loadEntities();

        foreach ($webhooks as $webhook) {
            $payload = json_decode($webhook['event_data'], true);
            $webhookDate = strtotime($payload['created_on']);
            $date = strtotime('-1 day');
            if ($webhookDate > $date) {
                continue;
            } 
            
            if (isset($this->transactionHandler::$transactionMapper[$webhook['event_type']])) {
                $order = $this->orderHandler->getOrder([
                    'entity_id' => $webhook['order_id']
                ]);

                $transaction = $this->transactionHandler->hasTransaction(
                    $order,
                    $webhook['action_id']
                );

                if ($transaction) {
                    $type = $transaction->getTxnType();
                    $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

                    switch ($type) {
                        case 'authorization':
                            $childCapture = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_CAPTURE,
                                $order
                            );

                            $childVoid = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_VOID,
                                $order
                            );

                            if ($childCapture || $childVoid) {
                                $this->deleteEntity($webhook['id']);
                            }
                            break;

                        case 'capture':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            if ($parentAuth || $paymentMethod == 'checkoutcom_apm') {
                                $this->deleteEntity($webhook['id']);
                            }
                            break;

                        case 'void':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            if ($parentAuth) {
                                $this->deleteEntity($webhook['id']);
                            }
                            break;

                        case 'refund':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            $parentCapture = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_CAPTURE,
                                $order
                            );

                            if ($parentAuth && $parentCapture->getIsClosed() == '1') {
                                $this->deleteEntity($webhook['id']);
                            }
                            break;
                    }
                }
            } else {
                $this->deleteEntity($webhook['id']);
            }
        }
    }
}
