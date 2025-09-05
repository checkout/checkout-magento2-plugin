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

namespace CheckoutCom\Magento2\Observer\Backend;

use CheckoutCom\Magento2\Provider\FlowPaymentMethodSettings;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use CheckoutCom\Magento2\Model\Migration\ApmMigrator;

class ApmConfigChangedObserver implements ObserverInterface
{
    protected $apmMigrator;

    public function __construct(ApmMigrator $apmMigrator)
    {
        $this->apmMigrator = $apmMigrator;
    }

    public function execute(Observer $observer)
    {
        $changedPaths = $observer->getEvent()->getData('changed_paths') ?? [];

        if (is_array($changedPaths) && in_array(FlowPaymentMethodSettings::CONFIG_PAYMENT_OLD_APM_METHODS_LIST, $changedPaths)) {
            $eventWebsite = (int) $observer->getEvent()->getData('website') ?? 0;
            $this->apmMigrator->migrate($eventWebsite);
        }
    }
}