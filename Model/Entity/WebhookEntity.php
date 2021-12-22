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

namespace CheckoutCom\Magento2\Model\Entity;

use CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity as WebhookEntityResourceModel;

/**
 * Class WebhookEntity
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class WebhookEntity extends AbstractModel implements WebhookEntityInterface, IdentityInterface
{
    /**
     * Page cache tag
     *
     * @var string CACHE_TAG
     */
    const CACHE_TAG = 'webhook_entity';
    /**
     * $_cacheTag field
     *
     * @var string $_cacheTag
     */
    public $_cacheTag = 'webhook_entity';
    /**
     * Prefix of model events names
     *
     * @var string $_eventPrefix
     */
    public $_eventPrefix = 'webhook_entity';

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            WebhookEntityResourceModel::class
        );
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get the row id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Get the event id
     *
     * @return bool
     */
    public function getEventId()
    {
        return $this->getData(self::EVENT_ID);
    }

    /**
     * Get the event type
     *
     * @return bool
     */
    public function getEventType()
    {
        return $this->getData(self::EVENT_TYPE);
    }

    /**
     * Get the event data
     *
     * @return string
     */
    public function getEventData()
    {
        return $this->getData(self::EVENT_DATA);
    }

    /**
     * Get the order id
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set the row id
     *
     * @param int $id
     *
     * @return WebhookEntityInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Set the event id
     *
     * @param bool $eventId
     *
     * @return WebhookEntityInterface
     */
    public function setEventId($eventId)
    {
        return $this->setData(self::EVENT_ID, $eventId);
    }

    /**
     * Set the event type
     *
     * @param bool $eventType
     *
     * @return WebhookEntityInterface
     */
    public function setEventType($eventType)
    {
        return $this->setData(self::EVENT_TYPE, $eventType);
    }

    /**
     * Set the event data
     *
     * @param string $eventData
     *
     * @return WebhookEntityInterface
     */
    public function setEventData($eventData)
    {
        return $this->setData(self::EVENT_DATA, $eventData);
    }

    /**
     * Set the order id
     *
     * @param string $orderId
     *
     * @return WebhookEntityInterface
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Set datetime webhook is received
     *
     * @return WebhookEntityInterface
     */
    public function setReceivedTime()
    {
        return $this->setData(self::RECEIVED_AT, date("Y-m-d H:i:s"));
    }

    /**
     * Set datetime webhook is processed
     *
     * @return WebhookEntityInterface
     */
    public function setProcessedTime()
    {
        return $this->setData(self::PROCESSED_AT, date("Y-m-d H:i:s"));
    }

    /**
     * Set if a webhook has been processed
     *
     * @param bool $bool
     *
     * @return WebhookEntityInterface
     */
    public function setProcessed($bool)
    {
        return $this->setData(self::PROCESSED, $bool);
    }
}
