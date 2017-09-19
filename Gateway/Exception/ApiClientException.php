<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Exception;

use Exception;

class ApiClientException extends Exception {

    /**
     * @var string
     */
    protected $eventId;

    /**
     * ApiClientException constructor.
     * @param string $message
     * @param int $code
     * @param Exception $eventId
     * @param Exception|null $previous
     */
    public function __construct($message, $code, $eventId, Exception $previous = null) {
        parent::__construct($message, (int) $code, $previous);

        $this->eventId = (string) $eventId;
    }

    /**
     * Returns the event ID.
     *
     * @return string
     */
    public function getEventId() {
        return $this->eventId;
    }

    /**
     * Returns the message with the code and internal message.
     *
     * @return string
     */
    public function getFullMessage() {
        return 'Error Code ' . $this->getCode() . ': ' . $this->getMessage();
    }

}
