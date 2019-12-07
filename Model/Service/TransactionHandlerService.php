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
    public function getTransactions($order, $transactionType = null)
    {
        // Get the list of transactions
        $transactions = $this->transactionSearch
        ->create()
        ->addOrderIdFilter($order->getId());
        $transactions->getItems();

        // Filter by transaction type
        if ($transactionType && !empty($transactions)) {
            $filteredResult = [];
            foreach ($transactions as $transaction) {
                $condition = $transaction->getTxnType() == $transactionType
                && $transaction->getIsClosed() == $isClosed;
                if ($condition) {
                    $filteredResult[] = $transaction;
                }
            }

            return $filteredResult;
        }

        return $transactions;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $transactionId, $transactionType)
    {
        return $this->transactionBuilder
            ->setPayment($order->getPayment())
            ->setOrder($order)
            ->setTransactionId($transactionId)
            /*->setAdditionalInformation(
                [
                    Transaction::RAW_DETAILS => $this->buildDataArray($this->paymentData)
                ]
            )*/
            ->setFailSafe(true)
            ->build($transactionType);
    }
}
