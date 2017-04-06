<?php

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
