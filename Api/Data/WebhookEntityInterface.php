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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Api\Data;

interface WebhookEntityInterface
{
    /**
     * Constants for keys of data array.
     */
    const ID = 'id';
    const EVENT_ID = 'event_id';
    const EVENT_TYPE = 'event_type';
    const EVENT_DATA = 'event_data';
    const ORDER_ID = 'order_id';

    /**
     * Get the row id
     *
     * @return int
     */
    public function getId();

    /**
     * Get the event id
     *
     * @return string
     */
    public function getEventId();

    /**
     * Get the event type
     *
     * @return string
     */
    public function getEventType();

    /**
     * Get the event data
     *
     * @return string
     */
    public function getEventData();

    /**
     * Get the order id
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Set the row id
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setId($rowId);

    /**
     * Set the event id
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setEventId($eventId);

    /**
     * Set the event type
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setEventType($eventType);

    /**
     * Set the event data
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setEventData($eventData);

    /**
     * Set the order id
     *
     * @return \CheckoutCom\Magento2\Api\Data\WebhookEntityInterface
     */
    public function setOrderId($orderId);
}
