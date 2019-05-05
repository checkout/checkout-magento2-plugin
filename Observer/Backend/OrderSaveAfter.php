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
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->transactionHandler = $transactionHandler;
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
            // Create the authorization transaction
            $this->transactionHandler->createTransaction
            (
                $order,
                Transaction::TYPE_AUTH
            );
        }
        
        return $this;
    }
}
