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

namespace CheckoutCom\Magento2\Cron;

use CheckoutCom\Magento2\Model\Service\WebhookHandlerService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Webhooks
 */
class Webhooks
{
    protected LoggerInterface $logger;
    private WebhookHandlerService $webhookHandler;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        WebhookHandlerService $webhookHandler,
        ScopeConfigInterface $scopeConfig
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
    public function execute(): void
    {
        $clean = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/webhooks_table_clean',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        $cleanOn = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/webhooks_clean_on',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        $webhooksTableEnabled = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/webhooks_table_enabled',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if ($webhooksTableEnabled && (!$clean || $cleanOn !== 'cron')) {
            return;
        }


        $this->webhookHandler->clean((bool)$webhooksTableEnabled);
        $this->logger->info('Webhook table has been cleaned.');
    }
}
