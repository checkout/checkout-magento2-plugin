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
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var BuilderInterface
     */
    public $transactionBuilder;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearch,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
    ) {
        $this->transactionSearch  = $transactionSearch;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * Get the transactions for an order.
     */
    public function getTransactions($orderId, $fields = null)
    {
        // Set the filters
        if (!empty($fields)) {
            foreach ($fields as $key => $value) {
                $this->searchCriteriaBuilder->addFilter(
                    $key,
                    $value,
                    'eq'
                );
            }
        }

        // Create the search filters
        $filters = $this->searchCriteriaBuilder->create();

        // Get the list of transactions
        $transactions = $this->transactionSearch->create()
        ->addOrderIdFilter($orderId)
        ->setSearchCriteria($filters);

        return  $transactions->getItems();        ;
    }

    /**
     * Get the transactions for an order.
     */
    public function hasTransaction($order, $transactionId)
    {
        // Set the filter
        $this->searchCriteriaBuilder->addFilter(
            'txn_id',
            $transactionId,
            'eq'
        );

        // Create the search filter
        $filter = $this->searchCriteriaBuilder->create();

        // Get the transaction
        $transaction = $this->transactionSearch->create()
        ->addOrderIdFilter($orderId)
        ->setSearchCriteria($filter);

        return !empty($transaction->getItems())
        ? true : false;  
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
