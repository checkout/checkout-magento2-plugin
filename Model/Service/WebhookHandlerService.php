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
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var WebhookEntityFactory
     */
    public $webhookEntityFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * WebhookHandlerService constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\FileEntityFactory $webhookEntityFactory,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->transactionHandler = $transactionHandler;
        $this->orderHandler = $orderHandler;
        $this->webhookEntityFactory = $webhookEntityFactory;
        $this->logger = $logger;
    }

    /**
     * handle an incoming webhook.
     */
    public function handleWebhook($order, $transactionType, $data = null)
    {
        // Prepare parameters
        $this->setProperties($order);

        // Process the transaction
        switch ($transactionType) {
            case Transaction::TYPE_AUTH:
                $this->handleAuthorization($transactionType, $data);
                break;

            case Transaction::TYPE_CAPTURE:
                $this->handleCapture($transactionType, $data);
                break;

            case Transaction::TYPE_VOID:
                $this->handleVoid($transactionType, $data);
                break;

            case Transaction::TYPE_REFUND:
                $this->handleRefund($transactionType, $data);
                break;

            default:
                $this->handleEvent($data);
        }

        // Return the order
        return $this->order;
    }

    /**
     * Handle the incoming webhook.
     */
    public function handle($data)
    {
        try {
            // Get a webhook entity instance
            $entity = $this->webhookEntityFactory->create();

            // Set the fields values
            $entity->setData($key, $value);

            // Save the entity
            $entity->save();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Save the incoming webhook.
     */
    public function save($payload)
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
                $entity->setData('entity_id', $payload->id);
                $entity->setData('entity_type', $payload->type);
                $entity->setData(
                    'entity_data',
                    json_encode($payload)
                );
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
