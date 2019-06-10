<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
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