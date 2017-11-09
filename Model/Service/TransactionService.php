<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Framework\Message\ManagerInterface;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

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
     * TransactionService constructor.
     * @param BuilderInterface $transactionBuilder
     */
    public function __construct(BuilderInterface $transactionBuilder, ManagerInterface $messageManager) {
        $this->transactionBuilder = $transactionBuilder;
        $this->messageManager     = $messageManager;
    }

    public function execute($order = null, $paymentData = array()) {
        try {
            // Prepare payment object
            $payment = $order->getPayment();
            $payment->setMethod(ConfigProvider::CODE); 
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData]);

            // Formatted price
            $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
 
            // Prepare transaction
            $transaction = $this->transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['id'])
            ->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData])
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);
 
            // Add transaction to payment
            $payment->addTransactionCommentsToOrder($transaction, __('The authorized amount is %1.', $formatedPrice));
            $payment->setParentTransactionId(null);

            // Save payment, transaction and order
            $payment->save();
            $order->save();
            $transaction->save();
 
            return  $transaction->getTransactionId();

        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
    }
}
