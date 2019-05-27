<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;

class TransactionHandlerService
{
    /**
     * @var array
     */
    protected static $transactionMapper = [
        'Authorization' => Transaction::TYPE_AUTH,
        'Capture' => Transaction::TYPE_CAPTURE,
        'Refund' => Transaction::TYPE_REFUND,
        'Void' => Transaction::TYPE_VOID
    ];

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
     * @var Utilities
     */
    protected $utilities;

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
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->transactionBuilder    = $transactionBuilder;
        $this->messageManager        = $messageManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder         = $filterBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
        $this->utilities             = $utilities;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $transactionMode, $data = null)
    {
        // Get a transaction id
        $paymentData = $data ? $data : $this->utilities->getPaymentData($order);
        $tid = $paymentData['id'];

        // Get a method id
        $methodId = $order->getPayment()
            ->getMethodInstance()
            ->getCode();
    
        // Process the transaction
        try {
            // Prepare payment object
            $payment = $order->getPayment();
            $payment->setMethod($methodId);
            $payment->setLastTransId($tid);
            $payment->setTransactionId($tid);
            $payment->setIsTransactionClosed(false);

            // Formatted price
            $formatedPrice = $order->getBaseCurrency()
                ->formatTxt(
                    $order->getGrandTotal()
                );

            // Prepare transaction
            $transaction = $this->transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($tid)
                ->setAdditionalInformation(
                    [
                        Transaction::RAW_DETAILS => $this->buildDataArray($paymentData)
                    ]
                )
                ->setFailSafe(true)
                ->build($transactionMode);

            // Add an authorization transaction to the payment
            if ($transactionMode == Transaction::TYPE_AUTH) {
                // Add order comments
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    __('The authorized amount is %1.', $formatedPrice)
                );

                // Set the parent transaction id
                $payment->setParentTransactionId(null);

                // Set the order status
                $order->setStatus(
                    $this->config->getValue('order_status_authorized')
                );
            }

            // Save payment, transaction and order
            $payment->save();
            $transaction->save();
            $order->save();

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
     * Build a flat array from the gateway response.
     */
    public function buildDataArray($gatewayResponse) {
        // Prepare the output array
        $output = [];

        // Remove the _links key
        if (isset($gatewayResponse['_links'])) unset($gatewayResponse['_links']);

        // Process the remaining data
        foreach ($gatewayResponse as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $output[$key . '_' . $k] = $v;
                }
            }
            else  {
                $output[$key] = $val;
            }
        }

        return $output;
    }

    /**
     * Get transactions for an order.
     */
    public function getTransactions($order, $transactionType = null)
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

            // Get the list of transactions
            $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();

            // Filter by transaction type
            if ($transactionType && count($transactions) > 0) {
                $filteredResult = [];
                foreach ($transactions as $transaction) {
                    if ($transaction->getTxnType() == $transactionType) {
                        $filteredResult[] = $transaction;
                    }
                }

                return $filteredResult;
            }

            return $transactions;

        } catch (Exception $e) {
            return false;
        }
    }
}