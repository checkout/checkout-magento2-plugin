<?php
namespace CheckoutCom\Magento2\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /*
         * Start setup
         */
        $installer = $setup;
        $installer->startSetup();

        /*
         * Drop tables if exists
         */
        $installer->getConnection()->dropTable($installer->getTable('cko_m2_plans'));
        $installer->getConnection()->dropTable($installer->getTable('cko_m2_subscriptions'));

        /*
         * Create table cko_m2_plans
         */
        $table = $installer->getConnection()->newTable($installer->getTable('cko_m2_plans'))
        ->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'nullable' => false,
                'primary'  => true,
                'unsigned' => true,
            ],
            'Plan ID'
        )
        ->addColumn(
            'plan_name',
            Table::TYPE_TEXT,
            255,
            ['nullable => false'],
            'Plan Name'
        )
        ->addColumn(
            'track_id',
            Table::TYPE_TEXT,
            255,
            [],
            'Plan Track ID'
        )
        ->addColumn(
            'auto_cap_time',
            Table::TYPE_DECIMAL,
            '10,2',
            ['nullable => false'],
            'Plan Auto Capture Time'
        )
        ->addColumn(
            'currency',
            Table::TYPE_TEXT,
            255,
            [],
            'Plan Currency'
        )
        ->addColumn(
            'plan_value',
            Table::TYPE_DECIMAL,
            '10,2',
            ['nullable => false'],
            'Plan Value'
        )
        ->addColumn(
            'cycle',
            Table::TYPE_TEXT,
            255,
            [],
            'Plan Cycle'
        )
        ->addColumn(
            'recurring_count',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Plan Recurring Count'
        )
        ->addColumn(
            'plan_status',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Plan Status'
        )
        ->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Plan Created At'
        )
        ->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Plan Updated At'
        )
        ->setComment('Payment Plan Table');
        $installer->getConnection()->createTable($table);

        /*
         * Create table cko_m2_subscriptions
         */
        $table = $installer->getConnection()->newTable($installer->getTable('cko_m2_subscriptions'))
        ->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'nullable' => false,
                'primary'  => true,
                'unsigned' => true,
            ],
            'Subscription ID'
        )
        ->addColumn(
            'plan_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable => false'],
            'Plan id'
        )
        ->addColumn(
            'card_id',
            Table::TYPE_TEXT,
            255,
            [],
            'Card ID'
        )
        ->addColumn(
            'user_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable => false'],
            'User ID'
        )
        ->addColumn(
            'track_id',
            Table::TYPE_TEXT,
            255,
            [],
            'Subscription Track ID'
        )
        ->addColumn(
            'recurring_count_left',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Subscription Recurring Count Left'
        )
        ->addColumn(
            'total_collection_count',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Subscription Total Collection Count'
        )
        ->addColumn(
            'total_collection_value',
            Table::TYPE_DECIMAL,
            '10,2',
            ['nullable' => false],
            'Subscription Total Collection Value'
        )
        ->addColumn(
            'start_date',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Subscription start date'
        )
        ->addColumn(
            'previous_recurring_date',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Subscription previous recurring date'
        )
        ->addColumn(
            'next_recurring_date',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Subscription next recurring date'
        )
        ->addColumn(
            'subscription_status',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Subscription Status'
        )
        ->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Subscription Created At'
        )
        ->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Subscription Updated At'
        )
        ->setComment('Subscription Table');
        $installer->getConnection()->createTable($table);        

        /*
         * End setup
         */
        $installer->endSetup();
    }
}