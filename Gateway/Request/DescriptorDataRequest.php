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

class DescriptorDataRequest extends AbstractRequest {

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {
        
        $result = [];
       
        if($this->config->isDescriptorEnabled()) {
            
            $name = $this->config->getDescriptorName();
            $city = $this->config->getDescriptorCity();
            
            $result = [
                'descriptor' => compact('name', 'city')
            ];
        }
        
        return $result;
    }
}
