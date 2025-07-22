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
     */
    public const string ID = 'id';
    /**
     * EVENT_ID constant
     */
    public const string EVENT_ID = 'event_id';
    /**
     * EVENT_TYPE constant
     */
    public const string EVENT_TYPE = 'event_type';
    /**
     * EVENT_DATA constant
     */
    public const string EVENT_DATA = 'event_data';
    /**
     * ORDER_ID constant
     */
    public const string ORDER_ID = 'order_id';
    /**
     * RECEIVED_AT constant
     */
    public const string RECEIVED_AT = 'received_at';
    /**
     * PROCESSED_AT constant
     */
    public const string PROCESSED_AT = 'processed_at';
    /**
     * PROCESSED constant
     */
    public const string PROCESSED = 'processed';

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
     * @return WebhookEntityInterface
     */
    public function setId($rowId): WebhookEntityInterface;

    /**
     * Set the event id
     *
     * @param string $eventId
     *
     * @return WebhookEntityInterface
     */
    public function setEventId(string $eventId): WebhookEntityInterface;

    /**
     * Set the event type
     *
     * @param string $eventType
     *
     * @return WebhookEntityInterface
     */
    public function setEventType(string $eventType): WebhookEntityInterface;

    /**
     * Set the event data
     *
     * @param string $eventData
     *
     * @return WebhookEntityInterface
     */
    public function setEventData(string $eventData): WebhookEntityInterface;

    /**
     * Set the order id
     *
     * @param int $orderId
     *
     * @return WebhookEntityInterface
     */
    public function setOrderId(int $orderId): WebhookEntityInterface;

    /**
     * Set datetime webhook is received
     *
     * @return WebhookEntityInterface
     */
    public function setReceivedTime(): WebhookEntityInterface;

    /**
     * Set datetime webhook is processed
     *
     * @return WebhookEntityInterface
     */
    public function setProcessedTime(): WebhookEntityInterface;

    /**
     * Set if a webhook has been processed
     *
     * @param bool $processed
     *
     * @return WebhookEntityInterface
     */
    public function setProcessed(bool $processed): WebhookEntityInterface;
}
