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

namespace CheckoutCom\Magento2\Setup\Patch\Schema;

use CheckoutCom\Magento2\Setup\Patch\Data\DeleteDuplicateEvents;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class AddUniqueConstraintOnEventId implements SchemaPatchInterface
{
    private SchemaSetupInterface $setup;

    public function __construct(
        SchemaSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    public static function getDependencies(): array
    {
        return [DeleteDuplicateEvents::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        $this->setup->startSetup();

        $this->setup->getConnection()->addIndex(
            $this->setup->getTable('checkoutcom_webhooks'),
            $this->setup->getIdxName(
                'CHECKOUTCOM_WEBHOOKS_EVENT_ID_UNIQUE',
                ['event_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['event_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );

        $this->setup->endSetup();

        return $this;
    }
}
