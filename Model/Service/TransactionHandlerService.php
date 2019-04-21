<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;

class TransactionHandlerService
{
    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var InvoiceHandlerService
     */
    protected $invoiceHandler;

     /**
      * @var Config
      */
    protected $config;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
        \CheckoutCom\Magento2\Model\Service\InvoiceHandlerService $invoiceHandler,
    	\CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->transactionBuilder    = $transactionBuilder;
        $this->messageManager        = $messageManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder         = $filterBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $paymentData, $transactionMode)
    {
        // Process the transaction
        try {
            // Prepare payment object
            $payment = $order->getPayment();
            $payment->setMethod($methodId);
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setAdditionalInformation(
                [
                    Transaction::RAW_DETAILS => (array) $paymentData
                ]
            );

            // Formatted price
            $formatedPrice = $order->getBaseCurrency()
                ->formatTxt(
                    $order->getGrandTotal()
                );
 
            // Prepare transaction
            $transaction = $this->transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    [
                        Transaction::RAW_DETAILS => (array) $paymentData
                    ]
                )
                ->setFailSafe(true)
                ->build($transactionMode);
 
            // Add authorization transaction to payment if needed
            if ($transactionMode == Transaction::TYPE_AUTH) {
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    __('The authorized amount is %1.', $formatedPrice)
                );
                $payment->setParentTransactionId(null);
            }

            // Save payment, transaction and order
            $payment->save();
            $order->save();
            $transaction->save();

            // Create the invoice
            // Todo - check this setting, add parameter to config
            if ($this->config->getValue('invoice_creation') == $transactionMode) {
                $this->invoiceHandler->processInvoice($order);
            }
 
            return $transaction->getTransactionId();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get all transactions for an order.
     */
    public function getTransactions($order)
    {
        try {
            // Payment filter
            $filters[] = $this->filterBuilder->setField('payment_id')
                ->setValue($order->getPayment()->getId())
                ->create();

            // Order filter
            $filters[] = $this->filterBuilder->setField('order_id')
                ->setValue($order->getId())
                ->create();

            // Build the search criteria
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilters($filters)
                ->create();

            return $this->transactionRepository->getList($searchCriteria)->getItems();
        } catch (Exception $e) {
            return false;
        }
    }
}