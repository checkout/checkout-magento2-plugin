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
use CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory;
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity\Collection;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class WebhookHandlerService
 */
class WebhookHandlerService
{
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
     * @param OrderHandlerService              $orderHandler
     * @param OrderStatusHandlerService        $orderStatusHandler
     * @param TransactionHandlerService        $transactionHandler
     * @param WebhookEntityFactory             $webhookEntityFactory
     * @param Config                           $config
     * @param Logger                           $logger
     * @param WebhookEntityRepositoryInterface $webhookEntityRepository
     */
    public function __construct(
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        TransactionHandlerService $transactionHandler,
        WebhookEntityFactory $webhookEntityFactory,
        Config $config,
        Logger $logger,
        WebhookEntityRepositoryInterface $webhookEntityRepository
    ) {
        $this->orderHandler            = $orderHandler;
        $this->orderStatusHandler      = $orderStatusHandler;
        $this->transactionHandler      = $transactionHandler;
        $this->webhookEntityFactory    = $webhookEntityFactory;
        $this->config                  = $config;
        $this->logger                  = $logger;
        $this->webhookEntityRepository = $webhookEntityRepository;
    }

    /**
     * Process a single incoming webhook
     *
     * @param OrderInterface $order
     * @param mixed          $payload
     *
     * @return void
     * @throws LocalizedException
     */
    public function processSingleWebhook(OrderInterface $order, $payload): void
    {
        if (isset($payload->data->action_id)) {

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
     * @param mixed          $payload
     *
     * @return void
     * @throws LocalizedException
     */
    public function processWithSave(OrderInterface $order, $payload): void
    {
        // Get all the webhooks to check for auth
        $webhooks = $this->loadWebhookEntities([
            'order_id' => $order->getId(),
        ]);

        if ($this->hasAuth($webhooks, $payload)) {
            // Save the payload
            $this->saveWebhookEntity($payload, $order);

            // Only return the single webhook that needs to be processed
            $webhook = $this->loadWebhookEntities([
                'order_id'  => $order->getId(),
                'action_id' => $payload->data->action_id,
            ]);

            // Handle the order status for the webhook
            $this->webhooksToProcess(
                $order,
                $webhook
            );

            $this->setProcessedTime($webhook);
        } else {
            // throw 400 as payment_approved has not been received or is still being processed
            throw new LocalizedException(__('payment_captured webhook refused'));
        }
    }

    /**
     * Description processWithoutSave function
     *
     * @param OrderInterface $order
     * @param mixed          $payload
     *
     * @return void
     * @throws Exception
     */
    public function processWithoutSave(OrderInterface $order, $payload): void
    {
        $webhooks   = [];
        $webhook    = [
            'event_id'   => $payload->id,
            'event_type' => $payload->type,
            'event_data' => json_encode($payload),
            'action_id'  => $payload->data->action_id,
            'payment_id' => $payload->data->id,
            'order_id'   => $order->getId(),
            'processed'  => false,
        ];
        $webhooks[] = $webhook;

        // Handle the order status for the webhook
        $this->webhooksToProcess(
            $order,
            $webhooks
        );
    }

    /**
     * Generate transactions and set order status from webhooks
     *
     * @param OrderInterface $order
     * @param mixed[][]      $webhooks
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
     * @param mixed[] $fields
     *
     * @return mixed[]
     */
    public function loadWebhookEntities(array $fields = []): array
    {
        // Create the collection
        $entities         = $this->webhookEntityFactory->create();
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

    /**
     * Description sortWebhooks function
     *
     * @param mixed[] $webhooks
     *
     * @return mixed[]
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
     * @param mixed          $payload
     * @param OrderInterface $order
     *
     * @return void
     */
    public function saveWebhookEntity($payload, OrderInterface $order): void
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
            $this->webhookEntityRepository->save($entity);
        }
    }

    /**
     * Description setProcessedTime function
     *
     * @param mixed[][] $webhooks
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
     * @param mixed[] $webhooks
     * @param mixed   $payload
     *
     * @return bool
     */
    public function hasAuth(array $webhooks, $payload): bool
    {
        if ($payload->type === 'payment_captured') {
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
    public function clean(): void
    {
        $webhooks = $this->loadWebhookEntities();

        foreach ($webhooks as $webhook) {
            $webhookDate = strtotime($webhook['received_at']);
            $date        = strtotime('-1 day');
            if ($webhookDate > $date && $webhook['processed']) {
                continue;
            }

            $this->webhookEntityRepository->deleteById((int)$webhook['id']);
        }
    }
}
