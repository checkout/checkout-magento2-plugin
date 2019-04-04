<?php

namespace CheckoutCom\Magento2\Model\Config\Backend;

class ConfigAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('Authorise')], ['value' => 1, 'label' => __('Authorise and Capture')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('Authorise'), 1 => __('Authorise and Capture')];
    }
}