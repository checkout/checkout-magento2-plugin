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

namespace CheckoutCom\Magento2\Cron;

/**
 * Class Webhooks.
 */
class Webhooks
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->webhookHandler = $webhookHandler;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Clean the webhooks table.
     *
     * @return void
     */
    public function execute()
    {
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
