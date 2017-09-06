<?php

namespace CheckoutCom\Magento2\Api\Data;

interface PlanInterface
{
    /**
     * Constants for keys of data array.
     */
    const ENTITY_ID = 'id';
    const NAME = 'plan_name';
    const STATUS = 'plan_status';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
 
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
     * Get Name.
     *
     * @return varchar
     */
    public function getName();
 
    /**
     * Set Name.
     */
    public function setName($name);
 
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