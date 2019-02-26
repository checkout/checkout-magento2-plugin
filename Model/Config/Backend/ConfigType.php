<?php

namespace CheckoutCom\Magento2\Model\Config\Backend;

class ConfigType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('Checkout.Frames')], ['value' => 1, 'label' => __('discussable')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('Checkout.Frames'), 1 => __('discussable')];
    }
}