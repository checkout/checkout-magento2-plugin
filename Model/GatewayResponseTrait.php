<?php

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
