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

    const DATE = 'date';
    const START_DATE = 'start-date';
    const END_DATE = 'end-date';

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
        $deleted = 0;

        foreach ($webhooks as $webhook) {
            $payload = json_decode($webhook['event_data'], true);

            $webhookTime = strtotime($webhook['received_at']);
            $timeBuffer = strtotime('-1 day');
            if ($webhookTime > $timeBuffer) {
                continue;
            }
            
            if ($date) {
                if ($date != $webhookTime) {
                    continue;
                }
            } elseif ($startDate || $endDate) {
                if ($startDate && $endDate) {
                    if ($startDate >= $webhookTime || $endDate <= $webhookTime) {
                        continue;
                    }
                } elseif ($startDate) {
                    if ($startDate >= $webhookTime) {
                        continue;
                    }
                } else {
                    if ($endDate <= $webhookTime) {
                        continue;
                    }
                }
            }

            if ($webhook['processed']) {
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
            $output->writeln(print_r($webhook, true));
        } elseif ($output->isVeryVerbose()) {
            $output->writeln('Deleting Webhook ID = ' . $webhook['id']);
        }
        
        return $output;
    }
}
