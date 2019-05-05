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

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var BackendAuthSession
     */
    protected $backendAuthSession;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->orderHandler = $orderHandler;
    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer)
    {
        // Get the order
        $order = $observer->getEvent()->getOrder();

        // Get the method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        if ($this->backendAuthSession->isLoggedIn() && $methodId == 'checkoutcom_moto') {
               // Process the order
               $order = $this->orderHandler->setMethodId($methodId)
               ->processTransactions($order);
        }
        
        return $this;
    }
}
