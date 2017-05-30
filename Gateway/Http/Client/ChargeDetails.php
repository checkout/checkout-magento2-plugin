<?php

namespace CheckoutCom\Magento2\Gateway\Http\Client;

class ChargeDetails extends AbstractTransaction {

    /**
     * Returns the HTTP method.
     *
     * @return string
     */
    public function getMethod() {
        return 'GET';
    }

    /**
     * Returns the URI.
     *
     * @return string
     */
    public function getUri() {
        return 'charges/{chargeId}';
    }

}
