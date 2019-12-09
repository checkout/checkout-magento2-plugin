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
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    public $config;

    /**
     * WebhookHandlerService constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory $webhookEntityFactory,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->orderHandler = $orderHandler;
        $this->transactionHandler = $transactionHandler;
        $this->webhookEntityFactory = $webhookEntityFactory;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Handle the incoming webhook.
     */
    public function processWebhook($order, $payload)
    {
        try {
            // Save the payload
            $this->saveEntity($payload);

            // Get the saved webhook
            $webhooks = $this->loadEntities([
                'order_id' => $order->getId(),
                'action_id' => $payload->data->action_id
            ]);

            // Handle transaction for the webhook
            $this->transactionHandler->webhookToTransaction(
                $order,
                $webhook
            );

        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
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
        try {
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Convert a gateway to decimal value for processing.
     */
    public function amountFromGateway($amount, $order)
    {
        // Get the quote currency
        $currency = $order->getOrderCurrencyCode();

        // Get the x1 currency calculation mapping
        $currenciesX1 = explode(
            ',',
            $this->config->getValue('currencies_x1')
        );

        // Get the x1000 currency calculation mapping
        $currenciesX1000 = explode(
            ',',
            $this->config->getValue('currencies_x1000')
        );

        // Prepare the amount
        if (in_array($currency, $currenciesX1)) {
            return $amount;
        } elseif (in_array($currency, $currenciesX1000)) {
            return $amount/1000;
        } else {
            return $amount/100;
        }
    }
}
