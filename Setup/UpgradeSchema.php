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

namespace CheckoutCom\Magento2\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Zend_Db_Exception;

/**
 * Class UpgradeSchema
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Description upgrade function
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     * @throws Zend_Db_Exception
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        // Define the webhooks table
        if (!$installer->getConnection()->isTableExists('checkoutcom_webhooks')) {
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
                ->addColumn(
                    'received_at',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false, 'comment' => 'Received At']
                )
                ->addColumn(
                    'processed_at',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false, 'comment' => 'Processed At']
                )
                ->addColumn(
                    'processed',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false, 'default' => false, 'comment' => 'Processed']
                )
                ->addIndex($installer->getIdxName('checkoutcom_webhooks_index', ['id']), ['id'])
                ->setComment('Webhooks table');

            // Create the table
            $installer->getConnection()->createTable($table1);
        }

        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            if ($setup->getConnection()->isTableExists('checkoutcom_webhooks')) {
                $connection = $setup->getConnection();
                 $connection->addColumn(
                     'checkoutcom_webhooks',
                     'received_at',
                     ['type' => Table::TYPE_DATETIME,'nullable' => false, 'comment' => 'Received At']
                 );
                 $connection->addColumn(
                     'checkoutcom_webhooks',
                     'processed_at',
                     ['type' => Table::TYPE_DATETIME,'nullable' => false, 'comment' => 'Processed At']
                 );
                 $connection->addColumn(
                     'checkoutcom_webhooks',
                     'processed',
                     ['type' => Table::TYPE_BOOLEAN,'nullable' => false, 'default' => false, 'comment' => 'Processed']
                 );
            }
        }

        // End the setup
        $installer->endSetup();
    }
}
