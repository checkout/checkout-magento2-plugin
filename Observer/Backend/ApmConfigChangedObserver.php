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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Observer\Backend;

use CheckoutCom\Magento2\Model\Migration\ApmMigrator;
use CheckoutCom\Magento2\Model\Migration\EnableForAllBrowserMigrator;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use CheckoutCom\Magento2\Provider\FlowPaymentMethodSettings;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ApmConfigChangedObserver implements ObserverInterface
{
    protected ApmMigrator $apmMigrator;
    protected EnableForAllBrowserMigrator $enableForAllBrowserMigrator;
    protected LoggerInterface $logger;

    public function __construct(
        ApmMigrator $apmMigrator,
        EnableForAllBrowserMigrator $enableForAllBrowserMigrator,
        LoggerInterface $logger
    )
    {
        $this->apmMigrator = $apmMigrator;
        $this->enableForAllBrowserMigrator = $enableForAllBrowserMigrator;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $changedPaths = $observer->getEvent()->getData('changed_paths') ?? [];

        if (is_array($changedPaths) && in_array(FlowPaymentMethodSettings::CONFIG_PAYMENT_OLD_APM_METHODS_LIST, $changedPaths)) {
            $eventWebsite = (int) $observer->getEvent()->getData('website') ?? 0;
            $this->apmMigrator->migrate($eventWebsite);
        }

        try {
            if (is_array($changedPaths) && in_array(FlowGeneralSettings::CONFIG_SDK, $changedPaths)) {
                $eventWebsite = (int) $observer->getEvent()->getData('website') ?? 0;
               $this->enableForAllBrowserMigrator->checkEnableForAllBrowser($eventWebsite);
            }
        } catch (Exception $error) {
            $this->logger->error(sprintf('Unable to desactive Apple on all browser: %s', $error->getMessage()));
        }
    }
}
