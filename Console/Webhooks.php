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

namespace CheckoutCom\Magento2\Console;

use CheckoutCom\Magento2\Api\WebhookEntityRepositoryInterface;
use CheckoutCom\Magento2\Model\Service\WebhookHandlerService;
use CheckoutCom\Magento2\Model\Service\WebhookHandlerServiceFactory;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Webhooks
 */
class Webhooks extends Command
{
    /**
     * DATE constant
     *
     * @var string DATE
     */
    const DATE = 'date';
    /**
     * START_DATE constant
     *
     * @var string START_DATE
     */
    const START_DATE = 'start-date';
    /**
     * END_DATE constant
     *
     * @var string END_DATE
     */
    const END_DATE = 'end-date';
    /**
     * $state field
     *
     * @var State $state
     */
    protected $state;
    /**
     * $webhookHandlerFactory field
     *
     * @var WebhookHandlerServiceFactory $webhookHandlerFactory
     */
    private $webhookHandlerFactory;
    /**
     * $webhookEntityRepository field
     *
     * @var WebhookEntityRepositoryInterface $webhookEntityRepository
     */
    private $webhookEntityRepository;
    /**
     * $webhookHandler field
     *
     * @var WebhookHandlerService $webhookHandler
     */
    private $webhookHandler;

    /**
     * Webhook constructor
     *
     * @param State                            $state
     * @param WebhookEntityRepositoryInterface $webhookEntityRepository
     * @param WebhookHandlerServiceFactory     $webhookHandlerServiceFactory
     */
    public function __construct(
        State $state,
        WebhookEntityRepositoryInterface $webhookEntityRepository,
        WebhookHandlerServiceFactory $webhookHandlerServiceFactory
    ) {
        $this->state                   = $state;
        $this->webhookEntityRepository = $webhookEntityRepository;
        $this->webhookHandlerFactory   = $webhookHandlerServiceFactory;

        parent::__construct();
    }

    /**
     * Configures the cli name and parameters.
     *
     * @return void
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::DATE, 'd', InputOption::VALUE_OPTIONAL, 'Date (Y-m-d)'
            ),
            new InputOption(
                self::START_DATE, 's', InputOption::VALUE_OPTIONAL, 'Start Date (Y-m-d)'
            ),
            new InputOption(
                self::END_DATE, 'e', InputOption::VALUE_OPTIONAL, 'End Date (Y-m-d)'
            ),
        ];

        $this->setName('cko:webhooks:clean')
            ->setDescription('Remove processed webhooks from the webhooks table.')
            ->setDefinition($options);
    }

    /**
     * Description createRequiredObjects function
     *
     * @return void
     * @throws LocalizedException
     */
    protected function createRequiredObjects()
    {
        try {
            $areaCode = $this->state->getAreaCode();
        } catch (Exception $e) {
            $areaCode = null;
        }

        if (!$areaCode) {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        }

        $this->webhookHandler = $this->webhookHandlerFactory->create();
    }

    /**
     * Executes "cko:webhooks:clean" command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createRequiredObjects();

        $date      = $input->getOption(self::DATE);
        $startDate = $input->getOption(self::START_DATE);
        $endDate   = $input->getOption(self::END_DATE);

        $webhooks = $this->webhookHandler->loadWebhookEntities();
        $deleted  = 0;

        foreach ($webhooks as $webhook) {
            $payload     = json_decode($webhook['event_data'], true);
            $webhookDate = date('Y-m-d', strtotime($webhook['received_at']));

            $webhookTime = strtotime($webhook['received_at']);
            $timeBuffer  = strtotime('-1 day');
            if ($webhookTime > $timeBuffer) {
                continue;
            }

            if ($date) {
                if ($date != $webhookDate) {
                    continue;
                }
            } elseif ($startDate || $endDate) {
                if ($startDate && $endDate) {
                    if ($startDate > $webhookDate || $endDate < $webhookDate) {
                        continue;
                    }
                } elseif ($startDate) {
                    if ($startDate > $webhookDate) {
                        continue;
                    }
                } else {
                    if ($endDate < $webhookDate) {
                        continue;
                    }
                }
            }

            if ($webhook['processed']) {
                $this->outputWebhook($output, $webhook);
                $this->webhookEntityRepository->deleteById((int)$webhook['id']);
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
     * @param                 $webhook
     *
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
