<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

class ConfigOrderStatus implements \Magento\Framework\Option\ArrayInterface {

    /**
     * @var Collection 
     */
    protected $orderStatusCollection;

    /**
     * OrderStatus constructor.
     * @param Collection $statusCollection
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Status\Collection $orderStatusCollection
    ){
        $this->orderStatusCollection = $orderStatusCollection;
    }

    /**
     * Return the order status options
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
        // Return the options as array
        return $this->orderStatusCollection->toOptionArray();
    }   
}