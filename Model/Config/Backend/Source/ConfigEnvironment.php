<?php

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

class ConfigEnvironment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('Test')
            ],
            [
                'value' => 1,
                'label' => __('Production')
            ]
        ];
    }
}