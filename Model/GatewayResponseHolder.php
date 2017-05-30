<?php

namespace CheckoutCom\Magento2\Model;

class GatewayResponseHolder {

    use GatewayResponseTrait;

    /**
     * Determines if the holder has response.
     *
     * @return bool
     */
    public function hasCallbackResponse() {
        return count($this->gatewayResponse) > 1;
    }

}
