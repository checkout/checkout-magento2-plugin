<?php

/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 7
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Api;

use CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;
use CheckoutCom\Magento2\Api\WebhookEntityRepositoryInterface;
use CheckoutCom\Magento2\Model\Entity\WebhookEntity;
use CheckoutCom\Magento2\Model\Entity\WebhookEntityFactory;
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity as WebhookResource;
use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class WebhookEntityRepository
 */
class WebhookEntityRepository implements WebhookEntityRepositoryInterface
{
    /**
     * $resource $field
     *
     * @var WebhookResource $resource
     */
    private $resource;
    /**
     * $webhookFactory field
     *
     * @var WebhookEntityFactory $webhookFactory
     */
    private $webhookFactory;

    /**
     * @param WebhookResource      $resource
     * @param WebhookEntityFactory $entityFactory
     */
    public function __construct(
        WebhookResource $resource,
        WebhookEntityFactory $entityFactory
    ) {
        $this->resource       = $resource;
        $this->webhookFactory = $entityFactory;
    }

    /**
     * {@inheritDoc}
     *
     * @param WebhookEntityInterface $webhookEntity
     *
     * @return WebhookEntityRepositoryInterface
     * @throws AlreadyExistsException
     */
    public function save(WebhookEntityInterface $webhookEntity): WebhookEntityRepositoryInterface
    {
        $this->resource->save($webhookEntity);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $entityId
     *
     * @return WebhookEntityInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): WebhookEntityInterface
    {
        /** @var WebhookEntityInterface $webhookEntity */
        $webhookEntity = $this->webhookFactory->create();
        $this->resource->load($webhookEntity, $entityId);
        if (!$webhookEntity->getId()) {
            throw new NoSuchEntityException(__('Webhook with id "%1" does not exist.', $entityId));
        }

        return $webhookEntity;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $entityId
     *
     * @return bool
     * @throws Exception
     */
    public function deleteById(int $entityId): bool
    {
        /** @var WebhookEntityInterface $webhookEntity */
        $webhookEntity = $this->getById($entityId);
        $this->resource->delete($webhookEntity);

        return true;
    }
}
