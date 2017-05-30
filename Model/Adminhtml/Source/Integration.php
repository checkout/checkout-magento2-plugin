<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class Integration implements ArrayInterface {

    const INTEGRATION_HOSTED = 'hosted';
    const INTEGRATION_EMBEDDED = 'embedded';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::INTEGRATION_HOSTED,
                'label' => 'Hosted'
            ],
            [
                'value' => self::INTEGRATION_EMBEDDED,
                'label' => 'Embedded'
            ]        
        ];
    }

}