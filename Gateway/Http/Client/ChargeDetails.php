<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
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
