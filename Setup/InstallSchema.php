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

namespace CheckoutCom\Magento2\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for the module
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     * @throws Zend_Db_Exception
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        // Initialise the installer
        $installer = $setup;
        $installer->startSetup();

        // Define the webhooks table
        $table1 = $installer->getConnection()
            ->newTable($installer->getTable('checkoutcom_webhooks'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Webhook ID'
            )
            ->addColumn('event_id', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('event_type', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('event_data', Table::TYPE_TEXT, null, ['nullable' => false])
            ->addColumn('action_id', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('payment_id', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('order_id', Table::TYPE_INTEGER, null, ['nullable' => false])
            ->addColumn('received_at', Table::TYPE_DATETIME, null, ['nullable' => false, 'comment' => 'Received At'])
            ->addColumn('processed_at', Table::TYPE_DATETIME, null, ['nullable' => false, 'comment' => 'Processed At'])
            ->addColumn('processed', Table::TYPE_BOOLEAN, null, ['nullable' => false, 'default' => false, 'comment' => 'Processed'])
            ->addIndex($installer->getIdxName('checkoutcom_webhooks_index', ['id']), ['id'])
            ->setComment('Webhooks table');

        // Create the table
        $installer->getConnection()->createTable($table1);

        // End the setup
        $installer->endSetup();
    }
}
