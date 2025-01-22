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

use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity\Collection as WebhookEntityCollection;
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity\CollectionFactory as WebhookEntityCollectionFactory;
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity as WebhookEntityResourceModel;
use Magento\Framework\Data\Collection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DeleteDuplicateEvents implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface $moduleDataSetup */
    private $moduleDataSetup;

    /** @var WebhookEntityCollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var WebhookEntityResourceModel $webhookEntityResourceModel */
    private $webhookEntityResourceModel;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WebhookEntityCollectionFactory $collectionFactory,
        WebhookEntityResourceModel $webhookEntityResourceModel
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
        $this->webhookEntityResourceModel = $webhookEntityResourceModel;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * @throws \Exception
     */
    public function apply(): self
    {
        /** @var AdapterInterface $setup */
        $setup = $this->moduleDataSetup->getConnection()->startSetup();

        $duplicateEvents = $this->getDuplicateEvents($setup);
        $webhookEntities = $this->getWebhookEntitiesToDelete($duplicateEvents);

        foreach ($webhookEntities as $webhookEntity) {
            $this->webhookEntityResourceModel->delete($webhookEntity);
        }

        $setup->endSetup();

        return $this;
    }

    private function getDuplicateEvents(AdapterInterface $setup): array
    {
        $webhookTable = $setup->getTableName('checkoutcom_webhooks');
        $select = $setup->select();

        $query = $select->from($webhookTable, ['id', 'event_id', 'COUNT(*)'])
            ->group('event_id')
            ->having('COUNT(*) > 1');

        return $setup->fetchAll($query);
    }

    private function getWebhookEntitiesToDelete(array $duplicateEvents): array
    {
        $duplicateEventIds = array_column($duplicateEvents, 'event_id');
        $webhookIdsToKeep = array_column($duplicateEvents, 'id');

        /** @var WebhookEntityCollection $webhookEntityCollection */
        $webhookEntityCollection = $this->collectionFactory->create();
        $webhookEntityCollection
            ->addFieldToSelect(['id', 'event_id'])
            ->addFieldToFilter('event_id', ['in' => $duplicateEventIds])
            ->addFieldToFilter('id', ['nin' => $webhookIdsToKeep])
            ->addOrder('event_id', Collection::SORT_ORDER_ASC);

        return $webhookEntityCollection->getItems();
    }
}
