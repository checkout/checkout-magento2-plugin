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

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var BackendAuthSession
     */
    protected $backendAuthSession;

    /**
     * @var TransactionHandlerService
     */
    protected $transactionHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * OrderSaveAfter constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->transactionHandler = $transactionHandler;
        $this->orderHandler = $orderHandler;
    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer)
    {
        // Get the order
        $this->order = $observer->getEvent()->getOrder();

        // Get the method id
        $this->methodId = $this->order->getPayment()->getMethodInstance()->getCode();

        // Create the authorization transaction
        if ($this->needsMotoProcessing()) {
            $this->transactionHandler->createTransaction
            (
                $this->order,
                Transaction::TYPE_AUTH
            );
        }
        
        return $this;
    }

    /**
     * Checks if the MOTO logic should be triggered.
     */
    protected function needsMotoProcessing() {
        return $this->backendAuthSession->isLoggedIn()
        && $this->methodId == 'checkoutcom_moto'
        && !$this->orderHandler->hasTransaction($this->order, Transaction::TYPE_AUTH);
    }
}