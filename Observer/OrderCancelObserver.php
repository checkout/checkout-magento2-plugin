<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderCancelObserver implements ObserverInterface {

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    public function __construct(OrderInterface $order) {
         $this->orderInterface = $orderInterface;    
    }

    /**
     * Handles the observer for order cancellation.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) {
   
        $orderids = $observer->getEvent()->getOrderIds();     

        echo "<pre>";
        var_dump($orderids); 
        echo "</pre>";

        exit();
    }

}
