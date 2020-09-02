<?php
namespace CheckoutCom\Magento2\Console;

use Magento\Sales\Model\Order\Payment\Transaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Webhooks extends Command
{

    private const DATE = 'date';
    private const START_DATE = 'start-date';
    private const END_DATE = 'end-date';
    
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
        $options = [
            new InputOption(
                self::DATE,
                'd',
                InputOption::VALUE_OPTIONAL,
                'Date (Y-m-d)'
            ),
            new InputOption(
                self::START_DATE,
                's',
                InputOption::VALUE_OPTIONAL,
                'Start Date (Y-m-d)'
            ),
            new InputOption(
                self::END_DATE,
                'e',
                InputOption::VALUE_OPTIONAL,
                'End Date (Y-m-d)'
            )
        ];

        $this->setName('cko:webhooks:clean')
            ->setDescription('Remove processed webhooks from the webhooks table.')
            ->setDefinition($options);
    }

    /**
     * Executes "cko:webhooks:clean" command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getOption(self::DATE);
        $startDate = $input->getOption(self::START_DATE);
        $endDate = $input->getOption(self::END_DATE);

        $webhooks = $this->webhookHandler->loadEntities();

        foreach ($webhooks as $webhook) {
            $payload = json_decode($webhook['event_data'], true);
            $webhookDate = date('Y-m-d', strtotime($payload['created_on']));
            if ($date) {
                if ($date != $webhookDate) {
                    continue;
                }
            } elseif ($startDate || $endDate) {
                if ($startDate && $endDate) {
                    if ($startDate >= $webhookDate || $endDate <= $webhookDate ) {
                        continue;
                    }
                } elseif ($startDate) {
                    if ($startDate >= $webhookDate) {
                        continue;
                    }
                } else {
                    if ($endDate <= $webhookDate) {
                        continue;
                    }
                }
            }

            $deleted = 0;

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
                                $this->outputWebhook($output, $webhook);
                                $this->webhookHandler->deleteEntity($webhook['id']);
                                $deleted++;
                            }
                            break;

                        case 'capture':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            if ($parentAuth) {
                                $this->outputWebhook($output, $webhook);
                                $this->webhookHandler->deleteEntity($webhook['id']);
                                $deleted++;
                            }
                            break;

                        case 'void':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            if ($parentAuth) {
                                $this->outputWebhook($output, $webhook);
                                $this->webhookHandler->deleteEntity($webhook['id']);
                                $deleted++;
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
                                $this->outputWebhook($output, $webhook);
                                $this->webhookHandler->deleteEntity($webhook['id']);
                                $deleted++;
                            }
                            break;
                    }
                }
            } else {
                $this->outputWebhook($output, $webhook);
                $this->webhookHandler->deleteEntity($webhook['id']);
                $deleted++;
            }
        }
        if ($output->isVerbose()) {
            $output->writeln('Removed ' . $deleted . ' entries from the webhook table.');
        } else {
            $output->writeln("Webhook table has been cleaned.");
        }
    }
    
    /**
     * Output a webhook to the console.
     *
     * @param OutputInterface $output
     * @return OutputInterface
     */
    protected function outputWebhook(OutputInterface $output, $webhook) {
        if ($output->isDebug()) {
            $output->writeln('Deleting Webhook: ');
            $output->writeln(var_dump($webhook));
        } elseif ($output->isVeryVerbose()) {
            $output->writeln('Deleting Webhook ID = ' . $webhook['id']);
        }
        
        return $output;
    }
}
