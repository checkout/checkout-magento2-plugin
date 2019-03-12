<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Payment\Transaction;

class OrderSaveBefore implements \Magento\Framework\Event\ObserverInterface
{
 


    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(

    ) {

    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer)
    {
        return $this;
    }
}
