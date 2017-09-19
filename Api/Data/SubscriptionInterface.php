<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Api\Data;

interface SubscriptionInterface
{
    /**
     * Constants for keys of data array.
     */
    const ENTITY_ID = 'id';
    const STATUS = 'subscription_status';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const TRACK_ID = 'track_id';
 
    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();
 
    /**
     * Set EntityId.
     */
    public function setEntityId($entityId);

    /**
     * Get TrackId.
     */
    public function getTrackId();
 
    /**
     * Set TrackId.
     */
    public function setTrackId($entityId);

    /**
     * Get Status.
     *
     * @return int
     */
    public function getStatus();

    /**
     * Set Status.
     */
    public function setStatus($status);

    /**
     * Get UpdatedAt.
     *
     * @return timestamp
     */
    public function getUpdatedAt();

    /**
     * Set UpdatedAt.
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get CreatedAt.
     *
     * @return varchar
     */
    public function getCreatedAt();
 
    /**
     * Set CreatedAt.
     */
    public function setCreatedAt($createdAt);
}