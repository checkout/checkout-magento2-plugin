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

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Invoice;

/**
 * Class TransactionHandlerService.
 */
class TransactionHandlerService
{
    /**
     * @var array
     */
    public static $transactionMapper = [
        'payment_approved' => Transaction::TYPE_AUTH,
        'payment_captured' => Transaction::TYPE_CAPTURE,
        'payment_refunded' => Transaction::TYPE_REFUND,
        'payment_voided' => Transaction::TYPE_VOID
    ];

    /**
     * @var TransactionSearchResultInterfaceFactory
     */
    public $transactionSearch;

    /**
     * @var BuilderInterface
     */
    public $transactionBuilder;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearch,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
    ) {
        $this->transactionSearch  = $transactionSearch;
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * Get the transactions for an order.
     */
    public function getTransactions($orderId, $transactionId = null)
    {
        // Get the list of transactions
        $transactions = $this->transactionSearch->create()
        ->addOrderIdFilter($orderId);
        $transactions->getItems(); 

        // Filter the transactions
        if ($transactionId && !empty($transactions)) {
            $filteredResult = [];
            foreach ($transactions as $transaction) {
                $condition = $transaction->getTxnId() == $transactionId;
                if ($condition) {
                    $filteredResult[] = $transaction;
                }
            }

            return $filteredResult;
        }

        return $transactions;
    }

    /**
     * Get the transactions for an order.
     */
    public function hasTransaction($order, $transactionId)
    {
        $transaction = $this->getTransactions(
            $order->getId(),
            $transactionId
        );

        return !empty($transaction) ? true : false;  
    }

    /**
     * Generate transactions from webhooks.
     */
    public function webhookToTransaction($order, $webhook = [])
    {
        // Create the transactions
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
                $this->handleTransaction(
                    $order,
                    $webhook
                );
            }
        }
    }

    /**
     * Handle a webhook transaction.
     */
    public function handleTransaction($order, $webhook)
    {
        // Prepare the test condition
        $condition = $this->hasTransaction(
            $order,
            $webhook['action_id']
        );

        // Create a transaction if needed
        if (!$condition) {
            $this->buildTransaction(
                $order,
                $webhook['action_id'],
                self::$transactionMapper[$webhook['event_type']]
            );
        }
    }

    /**
     * Create a transaction for an order.
     */
    public function buildTransaction($order, $transactionId, $transactionType)
    {        
        // Get the order payment
        $payment = $order->getPayment();

        // Create the transaction
        $transaction = $this->transactionBuilder
        ->setPayment($payment)
        ->setOrder($order)
        ->setTransactionId($transactionId)
        /*->setAdditionalInformation(
            [
                Transaction::RAW_DETAILS => $this->buildDataArray($this->paymentData)
            ]
        )*/
        ->setFailSafe(true)
        ->build($transactionType);

        // Save 
        $transaction->save();
        $payment->save();
    }
}
