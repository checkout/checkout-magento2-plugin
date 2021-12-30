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
     * @param \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface $webhookEntity
     *
     * @return \CheckoutCom\Magento2\Api\WebhookEntityRepositoryInterface
     */
    public function save(\CheckoutCom\Magento2\Api\Data\WebhookEntityInterface $webhookEntity): WebhookEntityRepositoryInterface;

    /**
     * Get webhook by id
     *
     * @param int $entityId
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
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
}
