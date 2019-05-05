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
    public function createTransaction($order, $transactionMode)
    {
        // Get a transaction id
        $tid = $this->getTransactionId($order);

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
            // Todo - Add transaction info
            /*
            $payment->setAdditionalInformation(
                [
                    Transaction::RAW_DETAILS => $paymentData
                ]
            );
            */

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
                // Todo - Add transaction info
                /*
                ->setAdditionalInformation(
                    [
                        Transaction::RAW_DETAILS => $paymentData
                    ]
                )
                */
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
     * Get a gateway transaction id.
     */
    public function getTransactionId($order) {
        $paymentData = $this->utilities->getPaymentData($order);
        return isset($paymentData['id']) ? $paymentData['id'] : 'cko_' . time();
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