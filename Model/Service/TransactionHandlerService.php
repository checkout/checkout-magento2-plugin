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
     * @var Logger
     */
    protected $logger;

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
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->transactionBuilder    = $transactionBuilder;
        $this->messageManager        = $messageManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder         = $filterBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
        $this->utilities             = $utilities;
        $this->logger = $logger;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $transactionType, $data = null)
    {
        try {
            // Get the payment data
            $paymentData = $data 
            ? $this->utilities->objectToArray($data)
            : $this->utilities->getPaymentData($order);

            // Get a transaction id
            $tid = $this->getActionId($paymentData);

            // Get a method id
            $methodId = $order->getPayment()
                ->getMethodInstance()
                ->getCode();
    
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
                ->build($transactionType);

            // Add an authorization transaction to the order
            if ($transactionType == Transaction::TYPE_AUTH) {
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

            // Add a capture transaction to the order
            else if ($transactionType == Transaction::TYPE_CAPTURE) {
                // Set the parent transaction id
                $payment->setParentTransactionId(null);
                /*$parentTransaction = $this->getTransactions($order, $transactionType);
                $payment->setParentTransactionId(
                    $parentTransaction[0]->getTransactionId()
                );*/

                // Handle the invoice and capture comments
                if ($this->config->getValue('auto_invoice')) {
                    $this->invoiceHandler->processInvoice($order);
                }
                else {
                    // Add order comments
                    $payment->addTransactionCommentsToOrder(
                        $transaction,
                        __('The captured amount is %1. No invoice was created.', $formatedPrice)
                    );                    
                }

                // Set the order status
                $order->setStatus(
                    $this->config->getValue('order_status_captured')
                );
            }

            // Add a capture transaction to the payment
            else if ($transactionType == Transaction::TYPE_VOID) {
                // Add order comments
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    __('The voided amount is %1.', $formatedPrice)
                );

                // Set the parent transaction id
                $payment->setParentTransactionId(null);

                // Set the order status
                $order->setStatus(
                    $this->config->getValue('order_status_voided')
                );
            }

            else if ($transactionType == Transaction::TYPE_REFUND) {
                // Add order comments
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    __('The refunded amount is %1.', $formatedPrice)
                );

                // Set the parent transaction id
                $payment->setParentTransactionId(null);

                // Set the order status
                $order->setStatus(
                    $this->config->getValue('order_status_refunded')
                );
            }

            // Save payment, transaction and order
            $payment->save();
            $transaction->save();
            $order->save();

            return $transaction->getTransactionId();
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());

            return false;
        }
    }

    /**
     * Get the action id from a gateway response.
     */
    public function getActionId($response) {
        try {
            return $response['data']['action_id'] ?? $response['action_id'];
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Build a flat array from the gateway response.
     */
    public function buildDataArray($data) {
        try {
            // Prepare the fields to remove
            $remove = [
                '_links',
                'risk',
                'metadata',
                'customer',
                'source'
            ];

            // Return the clean array
            return array_diff_key($data, array_flip($remove));
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
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
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}