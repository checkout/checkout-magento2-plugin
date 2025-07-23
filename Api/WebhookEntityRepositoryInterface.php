<?php

/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 8
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Api;

use CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

/**
 * Interface WebhookEntityRepositoryInterface
 */
interface WebhookEntityRepositoryInterface
{
    /**
     * Save Webhook entity
     *
     * @param WebhookEntityInterface $webhookEntity
     *
     * @return WebhookEntityRepositoryInterface
     */
    public function save(WebhookEntityInterface $webhookEntity): WebhookEntityRepositoryInterface;

    /**
     * Get webhook by id
     *
     * @param int $entityId
     *
     * @return WebhookEntityInterface
     */
    public function getById(int $entityId): WebhookEntityInterface;

    /**
     * Delete webhook by id
     *
     * @param int $entityId
     *
     * @return bool
     */
    public function deleteById(int $entityId): bool;

    /**
     * Delete Webhook entity
     *
     * @param WebhookEntityInterface $webhookEntity
     *
     * @return WebhookEntityRepositoryInterface
     */
    public function delete(WebhookEntityInterface $webhookEntity): WebhookEntityRepositoryInterface;
}
