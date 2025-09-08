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

namespace CheckoutCom\Magento2\Setup\Patch\Data;

use CheckoutCom\Magento2\Model\Migration\ApmMigrator;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class MigrateApmToFlowApm implements DataPatchInterface
{
    protected ApmMigrator $apmMigrator;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        ApmMigrator $apmMigrator,
        StoreManagerInterface $storeManager
    ) {
        $this->apmMigrator = $apmMigrator;
        $this->storeManager = $storeManager;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->apmMigrator->migrate();

        $websites = $this->storeManager->getWebsites();
        /**
         * @var WebsiteInterface $website
         */
        foreach ($websites as $website) {
            $id = (int) $website->getId();
            $this->apmMigrator->migrate($id);
        }
    }
}
