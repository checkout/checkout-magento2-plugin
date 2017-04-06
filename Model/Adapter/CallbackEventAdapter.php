<?php

namespace CheckoutCom\Magento2\Model\Adapter;

class CallbackEventAdapter {

    /**
     * @var array
     */
    private static $map = [
        'succeeded' => 'authorize',
        'captured'  => 'capture',
        'refunded'  => 'refund',
        'voided'    => 'void',
    ];

    /**
     * Returns the target command name based on received gateway event type.
     *
     * @param string $eventType
     * @return string|null
     */
    public function getTargetCommandName($eventType) {
        $eventParts = explode('.', $eventType);
        $command    = null;

        if( array_key_exists(1, $eventParts)) {
            $command = self::$map[ $eventParts[1] ] ?? null;
        }

        return $command;
    }

}
