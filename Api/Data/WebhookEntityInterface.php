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

namespace CheckoutCom\Magento2\Api\Data;

/**
 * Interface WebhookEntityInterface
 */
interface WebhookEntityInterface
{
    /**
     * Constants for keys of data array.
     */
    /**
     * ID constant
     *
     * @var string ID
     */
    const ID = 'id';
    /**
     * EVENT_ID constant
     *
     * @var string EVENT_ID
     */
    const EVENT_ID = 'event_id';
    /**
     * EVENT_TYPE constant
     *
     * @var string EVENT_TYPE
     */
    const EVENT_TYPE = 'event_type';
    /**
     * EVENT_DATA constant
     *
     * @var string EVENT_DATA
     */
    const EVENT_DATA = 'event_data';
    /**
     * ORDER_ID constant
     *
     * @var string ORDER_ID
     */
    const ORDER_ID = 'order_id';
    /**
     * RECEIVED_AT constant
     *
     * @var string RECEIVED_AT
     */
    const RECEIVED_AT = 'received_at';
    /**
     * PROCESSED_AT constant
     *
     * @var string PROCESSED_AT
     */
    const PROCESSED_AT = 'processed_at';
    /**
     * PROCESSED constant
     *
     * @var string PROCESSED
     */
    const PROCESSED = 'processed';

    /**
     * Get the row id
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Get the event id
     *
     * @return string
     */
    public function getEventId(): string;

    /**
     * Get the event type
     *
     * @return string
     */
    public function getEventType(): string;

    /**
     * Get the event data
     *
     * @return string
     */
    public function getEventData(): string;

    /**
     * Get the order id
     *
     * @return string|null
     */
    public function getOrderId(): ?string;

    /**
     * Set the row id
     *
     * @param $rowId
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setId($rowId): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

    /**
     * Set the event id
     *
     * @param string $eventId
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setEventId(string $eventId): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

    /**
     * Set the event type
     *
     * @param string $eventType
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setEventType(string $eventType): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

    /**
     * Set the event data
     *
     * @param string $eventData
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setEventData(string $eventData): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

    /**
     * Set the order id
     *
     * @param int $orderId
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setOrderId(int $orderId): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

    /**
     * Set datetime webhook is received
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setReceivedTime(): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

    /**
     * Set datetime webhook is processed
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setProcessedTime(): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;

    /**
     * Set if a webhook has been processed
     *
     * @param bool $processed
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setProcessed(bool $processed): \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface;
}
