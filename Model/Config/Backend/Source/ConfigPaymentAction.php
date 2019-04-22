<?php

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

class ConfigPaymentAction implements \Magento\Framework\Option\ArrayInterface
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
                'value' => 'authorize',
                'label' => __('Authorise')
            ],
            [
                'value' => 'authorize_capture',
                'label' => __('Authorise and Capture')
            ]
        ];
    }

}