<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class Integration implements ArrayInterface {

    const INTEGRATION_WIDGET = 'widget';
    const INTEGRATION_HOSTED = 'hosted';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::INTEGRATION_WIDGET,
                'label' => 'Widget',
            ],
            [
                'value' => self::INTEGRATION_HOSTED,
                'label' => 'Hosted'
            ]
        ];
    }

}