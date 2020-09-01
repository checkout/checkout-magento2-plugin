<?php
namespace CheckoutCom\Magento2\Console;

use Magento\Sales\Model\Order\Payment\Transaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Webhooks extends Command
{

    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    public function __construct(
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler
    ) {
        $this->webhookHandler = $webhookHandler;
        $this->orderHandler = $orderHandler;
        $this->transactionHandler = $transactionHandler;
        parent::__construct();
    }
    
    protected function configure() 
    {
        $this->setName('cko:webhooks:clean');
        $this->setDescription('Remove processed webhooks from the webhooks table.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
        $output->writeln("Webhook table has been cleaned successfully.");
    }
}
