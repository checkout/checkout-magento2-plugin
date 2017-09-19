<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model;

use CheckoutCom\Magento2\Api\Data\PlanInterface;
use Magento\Framework\Model\AbstractModel;

class Plan extends AbstractModel implements PlanInterface
{
    const CACHE_TAG = 'cko_m2_plans';

    protected $_cacheTag = 'cko_m2_plans';

    protected $_eventPrefix = 'cko_m2_plans';

    protected function _construct()
    {
        $this->_init('CheckoutCom\Magento2\Model\ResourceModel\Plan');
    }

    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }
 
    /**
     * Set EntityId.
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }
 
    /**
     * Get Name.
     *
     * @return varchar
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }
 
    /**
     * Set Name.
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }
 
    /**
     * Get Status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }
 
    /**
     * Set Status.
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
 
    /**
     * Get UpdatedAt.
     *
     * @return timestamp
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }
 
    /**
     * Set UpdatedAt.
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
 
    /**
     * Get CreatedAt.
     *
     * @return varchar
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }
 
    /**
     * Set CreatedAt.
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}