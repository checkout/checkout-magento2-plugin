<?php

namespace CheckoutCom\Magento2\Gateway\Request;

use CheckoutCom\Magento2\Gateway\Config\Config;

class ThreeDSecureRequest extends AbstractRequest {

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject) {
        if($this->config->isVerify3DSecure()){
            return [
                'chargeMode' => 2,
                'attemptN3D' => filter_var($this->config->isAttemptN3D(), FILTER_VALIDATE_BOOLEAN),
                'options' => [
                    Config::CODE_3DSECURE => [
                        'required' => true,
                    ],
                ],
            ];
        }

        return [];
    }

}
