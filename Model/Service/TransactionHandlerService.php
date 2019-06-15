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
     * @var CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    private $creditMemoService;

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
        \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditMemoService,
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
        $this->creditMemoFactory     = $creditMemoFactory;
        $this->creditMemoService     = $creditMemoService;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
        $this->utilities             = $utilities;
        $this->logger                = $logger;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $transactionType, $data = null)
    {
        try {
            // Prepare the needed elements
            $this->prepareData($order, $transactionType, $data);

            // Process the transaction
            switch ($this->transactionType) {
                case Transaction::TYPE_AUTH:
                    $this->handleAuthorization();
                    break;

                case Transaction::TYPE_CAPTURE:
                    $this->handleCapture();
                    break;

                case Transaction::TYPE_VOID:
                    $this->handleVoid();
                    break;

                case Transaction::TYPE_REFUND:
                    $this->handleRefund();
                    break;
            }

            // Invoice handling
            $this->invoiceHandler->processInvoice(
                $order,
                $this->transaction
            );

            // Save the processed elements
            $this->saveData();

        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Prepare the required instance properties.
     */
    public function prepareData($order, $transactionType, $data) {
        try {
            // Assign the order
            $this->order = $order;

            // Assign the transaction type
            $this->transactionType = $transactionType;

            // Assign the payment data
            $this->paymentData = $data
            ? $this->utilities->objectToArray($data)
            : $this->utilities->getPaymentData($this->order);

            // Assign the method ID
            $this->methodId = $this->getMethodId();

            // Prepare the payment
            $this->payment = $this->buildPayment();

            // Prepare the transaction
            $this->transaction = $this->buildTransaction();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
        finally {
            return $this;
        }
    }

    /**
     * Process an authorization request.
     */
    public function handleAuthorization() {
        try {
            // Add order comments
            $this->payment->addTransactionCommentsToOrder(
                $this->transaction,
                __(
                    'The authorized amount is %1.',
                    $this->getAmount()
                )
            );

            // Set the parent transaction id
            $this->transaction->setParentTxnId(null);

            // Allow void
            $this->transaction->setIsClosed(0);

            // Set the order status
            $this->order->setStatus(
                $this->config->getValue('order_status_authorized')
            );
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Process a capture request.
     */
    public function handleCapture() {
        try {
            // Lock the previous auth
            $authTransaction = $this->hasTransaction($this->order, Transaction::TYPE_AUTH);

            // Set the parent transaction id
            if (isset($authTransaction[0])) {
                $authTransaction[0]->close();
                $this->transaction->setParentTxnId(
                    $authTransaction[0]->getTxnId()
                );
            }

            // Add order comments
            $this->payment->addTransactionCommentsToOrder(
                $this->transaction,
                __(
                    'The captured amount is %1.',
                    $this->getAmount()
                )
            );

            // Allow refund
            $this->transaction->setIsClosed(0);

            // Set the order status
            $this->order->setStatus(
                $this->config->getValue('order_status_captured')
            );
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Process a void request.
     */
    public function handleVoid() {
        try {
            // Lock the previous auth
            $authTransaction = $this->hasTransaction($this->order, Transaction::TYPE_AUTH);

            // Set the parent transaction id
            if (isset($authTransaction[0])) {
                $authTransaction[0]->close();
                $this->transaction->setParentTxnId(
                    $authTransaction[0]->getTxnId()
                );
            }

            // Add order comments
            $this->payment->addTransactionCommentsToOrder(
                $this->transaction,
                __(
                    'The voided amount is %1.',
                    $this->getAmount()
                )
            );

            // Lock the transaction
            $this->transaction->setIsClosed(1);

            // Set the order status
            $this->order->setStatus(
                $this->config->getValue('order_status_voided')
            );
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Process a refund request.
     */
    public function handleRefund() {
        try {
            // Prepare the refunded amount
            $amount = $this->paymentData['data']['amount']/100;

            // Load the invoice
            $invoice = $this->invoiceHandler->getInvoice($this->order);

            // Create a credit memo
            $creditMemo = $this->creditMemoFactory->createByOrder($this->order);
            $creditMemo->setInvoice($invoice);
            $creditMemo->setBaseGrandTotal($amount);

            // Refund
            $this->creditMemoService->refund($creditMemo);

            // Update the order
            $this->order->setTotalRefunded($amount + $this->order->getTotalRefunded());

            // Lock the previous capture
            $captTransaction = $this->hasTransaction(Transaction::TYPE_CAPTURE);

            // Set the parent transaction id
            if (isset($captTransaction[0])) {
                $captTransaction[0]->close();
                $this->transaction->setParentTxnId(
                    $captTransaction[0]->getTxnId()
                );
            }

            // Lock the transaction
            $this->transaction->setIsClosed(1);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Checks if an order has a transactions.
     */
    public function hasTransaction($transactionType, $order = null, $isClosed = 0)
    {
        try {
            // Prepare the order
            $order = $order ? $order : $this->order;

            // Get the auth transactions
            $transactions = $this->getTransactions(
                $transactionType,
                $order,
                $isClosed
            );

            return count($transactions) > 0 ? $transactions : false;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Get the order method ID.
     */
    public function getMethodId() {
        try {
            return $this->order->getPayment()
            ->getMethodInstance()
            ->getCode();
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Get the order amount.
     */
    public function getAmount() {
        try {
            return $this->order->getBaseCurrency()
            ->formatTxt(
                $this->order->getGrandTotal()
            );
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
    
    /**
     * Save the required data.
     */
    public function saveData() {
        try {
            $this->payment->save();
            $this->transaction->save();
            $this->order->save();
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Get the action id from a gateway response.
     */
    public function getActionId() {
        try {
            return $this->paymentData['data']['action_id'] ?? $this->paymentData['action_id'];
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Build a transaction.
     */
    public function buildTransaction() {
        try {
            return $this->transactionBuilder
                ->setPayment($this->payment)
                ->setOrder($this->order)
                ->setTransactionId($this->getActionId())
                ->setAdditionalInformation(
                    [
                        Transaction::RAW_DETAILS => $this->buildDataArray($this->paymentData)
                    ]
                )
                ->setFailSafe(true)
                ->build($this->transactionType);
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Build a payment.
     */
    public function buildPayment() {
        try {
            return $this->order->getPayment()
                ->setMethod($this->methodId)
                ->setLastTransId($this->getActionId())
                ->setTransactionId($this->getActionId());
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
    public function getTransactions($transactionType = null, $order = null, $isClosed = 0)
    {
        try {
            // Prepare the order
            $order = $order ? $order : $this->order;

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
                    if ($transaction->getTxnType() == $transactionType && $transaction->getIsClosed() == $isClosed) {
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