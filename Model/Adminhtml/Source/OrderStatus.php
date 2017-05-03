<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class OrderStatus implements ArrayInterface {

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray() {
        return $this->getStatusOptions();
    }

    /**
     * Get the order status options
     *
     * @return array
     */
    public function getStatusOptions()
    {   
        // Load the object manager 
        $manager = \Magento\Framework\App\ObjectManager::getInstance(); 

        // Create the options list
        $options = $manager->create('Magento\Sales\Model\ResourceModel\Order\Status\Collection'); 

        // Return the options as array
        return $options->toOptionArray();
    }   
}