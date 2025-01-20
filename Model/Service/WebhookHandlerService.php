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
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity\Collection;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class WebhookHandlerService
 */
class WebhookHandlerService
{
    /**
     * @var Json
     */
    private $json;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * $orderStatusHandler field
     *
     * @var OrderStatusHandlerService $orderStatusHandler
     */
    private $orderStatusHandler;
    /**
     * $transactionHandler field
     *
     * @var TransactionHandlerService $transactionHandler
     */
    private $transactionHandler;
    /**
     * $webhookEntityFactory field
     *
     * @var WebhookEntityFactory $webhookEntityFactory
     */
    private $webhookEntityFactory;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $collection field
     *
     * @var Collection $collection
     */
    private $collection;
    /**
     * $webhookEntityRepository field
     *
     * @var WebhookEntityRepositoryInterface $webhookEntityRepository
     */
    private $webhookEntityRepository;

    /**
     * WebhookHandlerService constructor
     *
     * @param OrderHandlerService $orderHandler
     * @param OrderStatusHandlerService $orderStatusHandler
     * @param TransactionHandlerService $transactionHandler
     * @param WebhookEntityFactory $webhookEntityFactory
     * @param Config $config
     * @param Logger $logger
     * @param WebhookEntityRepositoryInterface $webhookEntityRepository
     * @param Json $json
     */
    public function __construct(
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        TransactionHandlerService $transactionHandler,
        WebhookEntityFactory $webhookEntityFactory,
        Config $config,
        Logger $logger,
        WebhookEntityRepositoryInterface $webhookEntityRepository,
        Json $json
    ) {
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->transactionHandler = $transactionHandler;
        $this->webhookEntityFactory = $webhookEntityFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->webhookEntityRepository = $webhookEntityRepository;
        $this->json = $json;
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
        if (isset($payload['data']['action_id'])) {
            if ($this->config->getValue('webhooks_table_enabled')) {
                $webhooks = $this->loadWebhookEntities([
                    'order_id' => $order->getId(),
                ]);

                if (!$this->hasAuth($webhooks, $payload)) {
                    throw new LocalizedException(__('payment_captured webhook refused for order %1', $order->getEntityId()));
                }
            }

            //Even if the webhooks_table_enabled is disabled, the events are saved temporary in the database to avoid multiple process of same event
            $this->processWithSave($order, $payload);

        } else {
            // Handle missing action ID
            $msg = sprintf(
                'Missing action ID for webhook with payment ID %s. Payload was: %s',
                $payload['data']['id'],
                $this->json->serialize($payload)
            );
            $this->logger->write($msg);
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
            // Save the payload
            $this->saveWebhookEntity($payload, $order);

            // Only return the single webhook that needs to be processed
            $webhook = $this->loadWebhookEntities([
                'order_id' => $order->getId(),
                'action_id' => $payload['data']['action_id'],
            ]);

            // Handle the order status for the webhook
            $this->webhooksToProcess(
                $order,
                $webhook
            );

            $this->setProcessedTime($webhook);
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
     * Description hasAuth function
     *
     * @param array $webhooks
     * @param array $payload
     *
     * @return bool
     */
    public function hasAuth(array $webhooks, array $payload): bool
    {
        if ($payload['type'] === 'payment_captured') {
            foreach ($webhooks as $webhook) {
                if ($webhook['event_type'] === 'payment_approved' || $webhook['event_type'] === 'payment_capture_pending') {
                    if ($webhook['processed']) {
                        return true;
                    }
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Clean the webhooks table
     *
     * @return void
     */
    public function clean(string $modifier = '-1 day'): void
    {
        try {
            $limitDate = (new \DateTime())->modify($modifier)->format('Y-m-d H:i:s');
        } catch (Exception $exception) {
            $this->logger->write($exception->getMessage());

            return;
        }
        $webhooks = $this->loadWebhookEntities(['received_at' => ['lteq' => $limitDate], 'processed' => ['eq' => 1]], true);

        /** @var WebhookEntity $webhook */
        foreach ($webhooks as $webhook) {
            $this->webhookEntityRepository->delete($webhook);
        }
    }
}
