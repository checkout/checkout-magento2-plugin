<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
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
