<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model;

trait GatewayResponseTrait {

    /**
     * @var array
     */
    protected $gatewayResponse = [];

    /**
     * Sets the given response to the object.
     *
     * @param array $gatewayResponse
     */
    public function setGatewayResponse(array $gatewayResponse) {
        $this->gatewayResponse = $gatewayResponse;
    }

    /**
     * Returns the response.
     *
     * @return array
     */
    public function getGatewayResponse() {
        return $this->gatewayResponse;
    }

}
