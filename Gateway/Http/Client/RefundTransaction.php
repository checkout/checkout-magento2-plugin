<?php

namespace CheckoutCom\Magento2\Gateway\Http\Client;

class RefundTransaction extends AbstractTransaction {

    /**
     * Returns the HTTP method.
     *
     * @return string
     */
    public function getMethod() {
        return 'POST';
    }

    /**
     * Returns the URI.
     *
     * @return string
     */
    public function getUri() {
        return 'charges/{chargeId}/refund';
    }

}
