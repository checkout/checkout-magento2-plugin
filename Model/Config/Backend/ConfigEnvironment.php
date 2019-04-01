<?php

namespace CheckoutCom\Magento2\Model\Config\Backend;

class ConfigEnvironment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('Test')], ['value' => 0, 'label' => __('Production')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [1 => __('Test'), 0 => __('Production')];
    }
}