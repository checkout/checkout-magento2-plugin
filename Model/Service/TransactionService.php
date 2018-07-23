<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Tools;

class TransactionService {

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * TransactionService constructor.
     * @param BuilderInterface $transactionBuilder
     * @param ManagerInterface $messageManager
    */
    public function __construct(BuilderInterface $transactionBuilder, ManagerInterface $messageManager, Tools $tools) {
        $this->transactionBuilder = $transactionBuilder;
        $this->messageManager     = $messageManager;
        $this->tools     = $tools;
    }

    public function createTransaction($order, $paymentData, $mode = null) {
        // Prepare the transaction mode
        $transactionMode = ($mode == 'authorization' || !$mode) ? Transaction::TYPE_AUTH : Transaction::TYPE_CAPTURE;

        // Create the transaction
        try {
            // Prepare payment object
            $payment = $order->getPayment();
            $payment->setMethod($this->tools->modmeta['tag']); 
            $payment->setLastTransId($paymentData['transactionReference']);
            $payment->setTransactionId($paymentData['transactionReference']);
            $payment->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData]);

            // Formatted price
            $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
 
            // Prepare transaction
            $transaction = $this->transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['transactionReference'])
            ->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData])
            ->setFailSafe(true)
            ->build($transactionMode);
 
            // Add transaction to payment
            $payment->addTransactionCommentsToOrder($transaction, __('The authorized amount is %1.', $formatedPrice));
            $payment->setParentTransactionId(null);

            // Save payment, transaction and order
            $payment->save();
            $order->save();
            $transaction->save();
 
            return $transaction->getTransactionId();

        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            return false;
        }
    }
}
