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

namespace CheckoutCom\Magento2\Model\Entity;

use CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity as WebhookEntityResourceModel;

/**
 * Class WebhookEntity
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
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            WebhookEntityResourceModel::class
        );
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get the row id
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->getData(self::ID);
    }

    /**
     * Get the event id
     *
     * @return string
     */
    public function getEventId(): string
    {
        return $this->getData(self::EVENT_ID);
    }

    /**
     * Get the event type
     *
     * @return string
     */
    public function getEventType(): string
    {
        return $this->getData(self::EVENT_TYPE);
    }

    /**
     * Get the event data
     *
     * @return string
     */
    public function getEventData(): string
    {
        return $this->getData(self::EVENT_DATA);
    }

    /**
     * Get the order id
     *
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set the row id
     *
     * @param $rowId
     *
     * @return WebhookEntityInterface
     */
    public function setId($rowId): WebhookEntityInterface
    {
        return $this->setData(self::ID, $rowId);
    }

    /**
     * Set the event id
     *
     * @param string $eventId
     *
     * @return WebhookEntityInterface
     */
    public function setEventId(string $eventId): WebhookEntityInterface
    {
        return $this->setData(self::EVENT_ID, $eventId);
    }

    /**
     * Set the event type
     *
     * @param string $eventType
     *
     * @return WebhookEntityInterface
     */
    public function setEventType(string $eventType): WebhookEntityInterface
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
    public function setEventData(string $eventData): WebhookEntityInterface
    {
        return $this->setData(self::EVENT_DATA, $eventData);
    }

    /**
     * Set the order id
     *
     * @param int $orderId
     *
     * @return WebhookEntityInterface
     */
    public function setOrderId(int $orderId): WebhookEntityInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Set datetime webhook is received
     *
     * @return WebhookEntityInterface
     */
    public function setReceivedTime(): WebhookEntityInterface
    {
        return $this->setData(self::RECEIVED_AT, date("Y-m-d H:i:s"));
    }

    /**
     * Set datetime webhook is processed
     *
     * @return WebhookEntityInterface
     */
    public function setProcessedTime(): WebhookEntityInterface
    {
        return $this->setData(self::PROCESSED_AT, date("Y-m-d H:i:s"));
    }

    /**
     * Set if a webhook has been processed
     *
     * @param bool $processed
     *
     * @return WebhookEntityInterface
     */
    public function setProcessed(bool $processed): WebhookEntityInterface
    {
        return $this->setData(self::PROCESSED, $processed);
    }
}
