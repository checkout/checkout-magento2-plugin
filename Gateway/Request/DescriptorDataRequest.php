<?php

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
