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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Api\WebhookEntityRepositoryInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Entity\WebhookEntity;
use CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory;
use DateTime;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class WebhookHandlerService
 */
class WebhookHandlerService
{
    /**
     * WEBHOOK_PAYMENT_TYPES constant
     */
    public const array WEBHOOK_PAYMENT_TYPES = ['payment_approved', 'payment_capture_pending', 'payment_captured'];

    public function __construct(
        private OrderHandlerService $orderHandler,
        private OrderStatusHandlerService $orderStatusHandler,
        private TransactionHandlerService $transactionHandler,
        private WebhookEntityFactory $webhookEntityFactory,
        private Config $config,
        private Logger $logger,
        private WebhookEntityRepositoryInterface $webhookEntityRepository,
        private Json $json,
        private ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Process a single incoming webhook
     *
     * @param OrderInterface $order
     * @param array $payload
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function processSingleWebhook(OrderInterface $order, array $payload): void
    {
        if (!isset($payload['data']['action_id'])) {
            throw new LocalizedException(
                __(
                    'Missing action ID for webhook with payment ID %1. Payload was: %2',
                    $payload['data']['id'] ?? '',
                    $this->json->serialize($payload)
                )
            );

            return;
        }

        if ($this->config->getValue('webhooks_table_enabled') ||
            (new \DateTime($order->getCreatedAt())) > (new \DateTime($this->config->getValue('verification_date')))
        ) {
            $this->checkAuth($order, $payload);
        }

        //Even if the webhooks_table_enabled is disabled, the events are saved temporary in the database to avoid multiple process of same event
        //And we can be sure that the captured event is processed after the approved event
        $this->processWithSave($order, $payload);
    }

    /**
     * @throws LocalizedException
     */
    public function checkAuth(OrderInterface $order, array $payload): void
    {
        $webhooks = $this->loadWebhookEntities([
            'order_id' => $order->getId(),
        ]);

        if ($payload['type'] !== 'payment_captured') {
            return;
        }

        foreach ($webhooks as $webhook) {
            if ($webhook['event_type'] === 'payment_approved' || $webhook['event_type'] === 'payment_capture_pending') {
                if ($webhook['processed']) {
                    return;
                }
            }
        }

        throw new LocalizedException(__('Captured event sent before approved event for order %1', $order->getEntityId()));
    }

    /**
     * Load a webhook collection.
     *
     * @param array $fields
     *
     * @return array
     */
    public function loadWebhookEntities(array $fields = [], bool $getItems = false): array
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

        if ($getItems) {
            return $this->collection->getItems();
        }

        $webhookEntitiesArr = $this->collection->getData();

        return $this->sortWebhooks($webhookEntitiesArr);
    }

    /**
     * Description sortWebhooks function
     *
     * @param array $webhooks
     *
     * @return array
     */
    public function sortWebhooks(array $webhooks): array
    {
        $sortedWebhooks = [];
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
                if ($webhook['event_type'] === 'payment_approved') {
                    array_unshift($sortedWebhooks, $webhook);
                } else {
                    $sortedWebhooks[] = $webhook;
                }
            }
        }

        return $sortedWebhooks;
    }

    /**
     * Description processWithSave function
     *
     * @param OrderInterface $order
     * @param array $payload
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function processWithSave(OrderInterface $order, array $payload): void
    {
        $this->saveWebhookEntity($payload, $order);

        $webhook = $this->loadWebhookEntities([
            'order_id' => $order->getId(),
            'action_id' => $payload['data']['action_id'],
        ]);

        $this->webhooksToProcess(
            $order,
            $webhook
        );

        $this->setProcessedTime($webhook);
    }

    /**
     * Save the incoming webhook
     *
     * @param array $payload
     * @param OrderInterface $order
     *
     * @return void
     */
    public function saveWebhookEntity(array $payload, OrderInterface $order): void
    {
        // Save the webhook
        if ($this->orderHandler->isOrder($order)) {
            // Get a webhook entity instance
            $entity = $this->webhookEntityFactory->create();

            // Set the fields values
            $entity->setData('event_id', $payload['id']);
            $entity->setData('event_type', $payload['type']);
            $entity->setData(
                'event_data',
                $this->json->serialize($payload)
            );
            $entity->setData('action_id', $payload['data']['action_id']);
            $entity->setData('payment_id', $payload['data']['id']);
            $entity->setData('order_id', $order->getId());
            $entity->setReceivedTime();
            $entity->setData('processed', false);

            // Save the entity
            $this->webhookEntityRepository->save($entity);
        }
    }

    /**
     * Generate transactions and set order status from webhooks
     *
     * @param OrderInterface $order
     * @param array[] $webhooks
     *
     * @return void
     * @throws Exception
     */
    public function webhooksToProcess(OrderInterface $order, array $webhooks = []): void
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

                $this->logger->additional($this->orderHandler->getOrderDetails($order), 'webhook');
            }
        }
    }

    /**
     * Description setProcessedTime function
     *
     * @param array[] $webhooks
     *
     * @return void
     */
    public function setProcessedTime(array $webhooks): void
    {
        // Get a webhook entity instance
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
                if (!$webhook['processed']) {
                    $entity = $this->webhookEntityRepository->getById((int)$webhook['id']);
                    $entity->setProcessed(true);
                    $entity->setProcessedTime();
                    $this->webhookEntityRepository->save($entity);
                }
            }
        }
    }

    /**
     * Process all webhooks for an order
     *
     * @param OrderInterface $order
     *
     * @return void
     * @throws Exception
     */
    public function processAllWebhooks(OrderInterface $order): void
    {
        // Get the webhook entities
        $webhooks = $this->loadWebhookEntities([
            'order_id' => $order->getId(),
        ]);

        $this->webhooksToProcess(
            $order,
            $webhooks
        );

        $this->setProcessedTime($webhooks);
    }

    /**
     * Clean the webhooks table
     *
     * @return void
     */
    public function clean(bool $dayIntervalFlag = true): void
    {
        try {
            //Keep one hour to avoid duplicata
            $limitDate = (new DateTime())->modify($dayIntervalFlag ? '-1 day' : '-1 hour')->format('Y-m-d H:i:s');
        } catch (Exception $exception) {
            $this->logger->write($exception->getMessage());

            return;
        }

        $this->cleanPayment($limitDate);

        $this->cleanNonPayment($dayIntervalFlag, $limitDate);
    }

    protected function cleanPayment(string $limitDate): void
    {
        $orderIdsToClean = $this->getOrderIdsToClean($limitDate);

        $this->deleteWebhooksByField(['order_id' => ['in' => $orderIdsToClean], 'processed' => '1']);
    }

    protected function getOrderIdsToClean(string $limitDate): array
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from('checkoutcom_webhooks', ['order_id', 'COUNT(*)'])
            ->where('processed = 1')
            ->where('event_type IN (?)', self::WEBHOOK_PAYMENT_TYPES)
            ->where('processed_at <= ?', $limitDate)
            ->group('order_id')
            ->having('COUNT(*) >= 2');

        $eventsToDelete = $this->resourceConnection->getConnection()->fetchAll($select);

        return array_column($eventsToDelete, 'order_id');
    }

    protected function deleteWebhooksByField(array $fields): void
    {
        $webhooks = $this->loadWebhookEntities($fields, true);

        /** @var WebhookEntity $webhook */
        foreach ($webhooks as $webhook) {
            $this->webhookEntityRepository->delete($webhook);
        }
    }

    protected function cleanNonPayment(bool $checkDate, string $limitDate): void
    {
        $fields = [
            'processed' => ['eq' => 1],
            'event_type' => ['nin' => self::WEBHOOK_PAYMENT_TYPES],
        ];

        if ($checkDate) {
            $fields['received_at'] = ['lteq' => $limitDate];
        }

        $this->deleteWebhooksByField($fields);
    }
}


