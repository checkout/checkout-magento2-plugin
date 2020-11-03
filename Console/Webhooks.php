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

namespace CheckoutCom\Magento2\Console;

use Magento\Sales\Model\Order\Payment\Transaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Webhooks
 */
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

    /**
     * Webhooks constructor
     */
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

    /**
     * Configures the cli name and parameters.
     */
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

        $webhooks = $this->webhookHandler->loadWebhookEntities();

        foreach ($webhooks as $webhook) {
            $payload = json_decode($webhook['event_data'], true);
            $webhookDate = date('Y-m-d', strtotime($payload['created_on']));

            $webhookTime = strtotime($payload['created_on']);
            $timeBuffer = strtotime('-1 day');
            if ($webhookTime > $timeBuffer) {
                continue;
            }
            
            if ($date) {
                if ($date != $webhookDate) {
                    continue;
                }
            } elseif ($startDate || $endDate) {
                if ($startDate && $endDate) {
                    if ($startDate >= $webhookDate || $endDate <= $webhookDate) {
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
                                $this->outputWebhook($output, $webhook);
                                $this->webhookHandler->deleteWebhookEntity($webhook['id']);
                                $deleted++;
                            }
                            break;

                        case 'capture':
                            $parentAuth = $this->transactionHandler->getTransactionByType(
                                Transaction::TYPE_AUTH,
                                $order
                            );

                            if ($parentAuth || $paymentMethod == 'checkoutcom_apm') {
                                $this->outputWebhook($output, $webhook);
                                $this->webhookHandler->deleteWebhookEntity($webhook['id']);
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
                                $this->webhookHandler->deleteWebhookEntity($webhook['id']);
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
                                $this->webhookHandler->deleteWebhookEntity($webhook['id']);
                                $deleted++;
                            }
                            break;
                    }
                }
            } else {
                $this->outputWebhook($output, $webhook);
                $this->webhookHandler->deleteWebhookEntity($webhook['id']);
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
    protected function outputWebhook(OutputInterface $output, $webhook)
    {
        if ($output->isDebug()) {
            $output->writeln('Deleting Webhook: ');
            $output->writeln(var_dump($webhook));
        } elseif ($output->isVeryVerbose()) {
            $output->writeln('Deleting Webhook ID = ' . $webhook['id']);
        }
        
        return $output;
    }
}
