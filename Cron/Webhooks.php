<?php
namespace CheckoutCom\Magento2\Cron;

use Magento\Sales\Model\Order\Payment\Transaction;

class Webhooks {
    protected $logger;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler
    ) {
        $this->logger = $logger;
        $this->transactionHandler = $transactionHandler;
        $this->webhookHandler = $webhookHandler;
        $this->orderHandler = $orderHandler;
    }

    /**
     * Clean the webhooks table.
     *
     * @return void
     */
    public function execute() {
        $webhooks = $this->webhookHandler->loadEntities();

        foreach ($webhooks as $webhook) {
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
                                $this->webhookHandler->deleteEntity($webhook['id']);
                            }
                            break;

                        case 'capture':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            if ($parentAuth) {
                                $this->webhookHandler->deleteEntity($webhook['id']);
                            }
                            break;

                        case 'void':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            if ($parentAuth) {
                                $this->webhookHandler->deleteEntity($webhook['id']);
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

                            if ($parentAuth && $parentCapture) {
                                $this->webhookHandler->deleteEntity($webhook['id']);
                            }
                            break;
                    }
                }
            } else {
                $this->webhookHandler->deleteEntity($webhook['id']);
            }
        }
        
        $this->logger->info('Webhook table has been cleaned.');
    }
}
