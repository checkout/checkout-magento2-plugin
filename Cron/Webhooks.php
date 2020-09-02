<?php
namespace CheckoutCom\Magento2\Cron;

use Magento\Sales\Model\Order\Payment\Transaction;

class Webhooks 
{
    /**
     * @var LoggerInterface
     */
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

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->transactionHandler = $transactionHandler;
        $this->webhookHandler = $webhookHandler;
        $this->orderHandler = $orderHandler;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Clean the webhooks table.
     *
     * @return void
     */
    public function execute() {
        // Check if cron clean has been enabled
        $clean = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/webhooks_table_clean',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $cleanOn = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/webhooks_clean_on',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($clean && $cleanOn == 'cron') {
            $this->webhookHandler->clean();
            $this->logger->info('Webhook table has been cleaned.');
        }
        
    }
}
