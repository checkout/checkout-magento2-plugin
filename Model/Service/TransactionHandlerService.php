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
use Magento\Sales\Model\Order;

/**
 * Class TransactionHandlerService.
 */
class TransactionHandlerService
{
    /**
     * @var BuilderInterface
     */
    public $transactionBuilder;

    /**
     * @var TransactionSearchResultInterfaceFactory
     */
    public $transactionSearch;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    public $filterBuilder;

    /**
     * @var TransactionRepository
     */
    public $transactionRepository;

    /**
     * @var CreditmemoFactory
     */
    public $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    public $creditMemoService;

    /**
     * @var OrderSender
     */
    public $orderSender;

    /**
     * @var InvoiceHandlerService
     */
    public $invoiceHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearch,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditMemoService,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \CheckoutCom\Magento2\Model\Service\InvoiceHandlerService $invoiceHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->transactionBuilder    = $transactionBuilder;
        $this->transactionSearch     = $transactionSearch;
        $this->messageManager        = $messageManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder         = $filterBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->creditMemoFactory     = $creditMemoFactory;
        $this->creditMemoService     = $creditMemoService;
        $this->orderSender           = $orderSender;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
        $this->utilities             = $utilities;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $transactionType, $data = null)
    {
        // Prepare parameters
        $this->setProperties($order);

        // Process the transaction
        switch ($transactionType) {
            case Transaction::TYPE_AUTH:
                $this->handleAuthorization($transactionType, $data);
                break;

            case Transaction::TYPE_CAPTURE:
                $this->handleCapture($transactionType, $data);
                break;

            case Transaction::TYPE_VOID:
                $this->handleVoid($transactionType, $data);
                break;

            case Transaction::TYPE_REFUND:
                $this->handleRefund($transactionType, $data);
                break;
        }

        // Return the order
        return $this->order;
    }

    /**
     * Convert a gateway to decimal value for processing.
     */
    public function amountFromGateway($amount, $order)
    {
        // Get the quote currency
        $currency = $order->getOrderCurrencyCode();

        // Get the x1 currency calculation mapping
        $currenciesX1 = explode(
            ',',
            $this->config->getValue('currencies_x1')
        );

        // Get the x1000 currency calculation mapping
        $currenciesX1000 = explode(
            ',',
            $this->config->getValue('currencies_x1000')
        );

        // Prepare the amount
        if (in_array($currency, $currenciesX1)) {
            return $amount;
        } elseif (in_array($currency, $currenciesX1000)) {
            return $amount/1000;
        } else {
            return $amount/100;
        }
    }

    /**
     * Prepare the required instance properties.
     */
    public function setProperties($order)
    {
        // Assign the order
        $this->order = $order;

        // Assign the method ID
        $this->methodId = $this->order->getPayment()
        ->getMethodInstance()
        ->getCode();
    }

    /**
     * Prepare the required instance data.
     */
    public function prepareData($transactionType, $data)
    {
        // Assign the transaction type
        $this->transactionType = $transactionType;

        // Assign the payment data
        $this->paymentData = $data
        ? $this->utilities->objectToArray($data)
        : $this->utilities->getPaymentData($this->order);

        // Prepare the payment
        $this->payment = $this->buildPayment();

        // Prepare the transaction
        $this->transaction = $this->buildTransaction();

        return $this;
    }

    /**
     * Check if custom invoicing is needed.
     */
    public function needsCustomInvoicing()
    {
        return !isset($this->paymentData['data']['metadata']['isBackendCapture']);
    }

    /**
     * Check if custom comment is needed.
     */
    public function needsCustomComment()
    {
        return isset($this->paymentData['data']['metadata']['isFrontendRequest']);
    }

    /**
     * Process an authorization request.
     */
    public function handleAuthorization($transactionType, $data)
    {
        // Handle authorization
        $authTransaction = $this->hasTransaction(
            Transaction::TYPE_AUTH,
            $this->order
        );

        if (!$authTransaction) {
            // Prepare the data
            $this->prepareData($transactionType, $data);

            // Set the order status
            $this->setOrderStatus(
                'order_status_authorized',
                Order::STATE_PENDING_PAYMENT
            );

            // Add order comment
            $this->addOrderComment('The authorized amount is %1.');

            // Set the parent transaction id
            $this->transaction->setParentTxnId(null);

            // Allow void and capture
            $this->transaction->setIsClosed(0);

            // Check the email sender
            if ($this->config->getValue('order_email') == 'authorize') {
                $this->order->setCanSendNewEmailFlag(true);
                $this->orderSender->send($this->order, true);
            }

            // Save the data
            $this->payment->save();
            $this->transaction->save();
            $this->order->save();
        }
    }

    /**
     * Process a capture request.
     */
    public function handleCapture($transactionType, $data)
    {
        // Get the parent transaction
        $parentTransaction = $this->getParentTransaction(Transaction::TYPE_AUTH);

        // Handle the capture logic
        if ($parentTransaction) {
            // Prepare the data
            $this->prepareData($transactionType, $data);

            // Set the parent transaction id for the current transaction
            $this->transaction->setParentTxnId(
                $parentTransaction->getTxnId()
            );
        
            // Set the order status
            $this->setOrderStatus(
                'order_status_captured',
                Order::STATE_PROCESSING
            );

            // Allow refund
            $this->transaction->setIsClosed(0);

            // Check the email sender
            if ($this->config->getValue('order_email') == 'authorize_capture') {
                $this->order->setCanSendNewEmailFlag(true);
                $this->orderSender->send($this->order, true);
            }

            // Get the payment amount
            $paymentAmount = $this->utilities->formatDecimals(
                $this->amountFromGateway(
                    $this->paymentData['data']['amount'],
                    $this->order
                )
            );

            // Add custom comment only if request is from the frontend
            if ($this->needsCustomComment()) {
                $this->addOrderComment('The captured amount is %1.', $paymentAmount);
            }

            // Custom invoice handling only if it's not admin capture
            if ($this->needsCustomInvoicing()) {
                // Process the invoice
                $this->order = $this->invoiceHandler->processInvoice(
                    $this->order,
                    $this->transaction,
                    $paymentAmount
                );
            } else {
                // Get the order amount
                $orderAmount = $this->utilities->formatDecimals(
                    $this->order->getGrandTotal()
                );

                // Check the partial capture case
                if ($paymentAmount < $orderAmount) {
                    $parentTransaction->setIsClosed(0);
                    $parentTransaction->save();
                }
            }

            // Close the authorization transaction
            $parentTransaction->close();

            // Save the data
            $this->payment->save();
            $this->transaction->save();
            $this->order->save();
        }
    }

    /**
     * Process a void request.
     */
    public function handleVoid($transactionType, $data)
    {
        // Process teh void logic
        $parentTransaction = $this->getParentTransaction(Transaction::TYPE_AUTH);
        if ($parentTransaction) {
            // Prepare the data
            $this->prepareData($transactionType, $data);

            // Set the parent transaction id
            $parentTransaction->close();
            $this->transaction->setParentTxnId(
                $parentTransaction->getTxnId()
            );

            // Add order comment
            $this->addOrderComment('The voided amount is %1.');

            // Lock the transaction
            $this->transaction->setIsClosed(1);

            // Set the order status
            $this->setOrderStatus(
                'order_status_voided',
                'order_status_voided'
            );

            // Save the data
            $this->payment->save();
            $this->transaction->save();
            $this->order->save();
        }
    }

    /**
     * Process a refund request.
     */
    public function handleRefund($transactionType, $data)
    {
        // Process the refund logic
        $parentTransaction = $this->getParentTransaction(Transaction::TYPE_CAPTURE);
        if ($parentTransaction) {
            // Prepare the data
            $this->prepareData($transactionType, $data);

            // Set the parent transaction id
            $parentTransaction->close();
            $this->transaction->setParentTxnId(
                $parentTransaction->getTxnId()
            );
            
            // Prepare the refunded amount
            $amount = $this->amountFromGateway(
                $this->paymentData['data']['amount'],
                $this->order
            );

            // Load the invoice
            $invoice = $this->invoiceHandler->getInvoice($this->order);

            // Create a credit memo
            $creditMemo = $this->creditMemoFactory->createByOrder($this->order);
            $creditMemo->setInvoice($invoice);
            $creditMemo->setBaseGrandTotal($amount);
            $creditMemo->setGrandTotal($amount);

            // Refund
            $this->creditMemoService->refund($creditMemo);

            // Update the order
            if ($this->order->getGrandTotal() == $this->order->getTotalRefunded()) {
                // Order status
                $this->setOrderStatus(
                    'order_status_refunded',
                    'order_status_refunded'
                );

                // Transaction state
                $this->transaction->setIsClosed(1);
            } else {
                $this->setOrderStatus(
                    'order_status_refunded_partial',
                    'order_status_refunded_partial'
                );

                // Transaction state
                $this->transaction->setIsClosed(0);
            }

            // Update the refunded amount
            $this->order->setTotalRefunded($this->getCreditMemosTotal());

            // Save the data
            $this->payment->save();
            $this->transaction->save();
            $this->order->save();
        }
    }

    /**
     * Get the total credit memos amount.
     */
    public function getCreditMemosTotal()
    {
        $total = 0;
        $creditMemos = $this->order->getCreditmemosCollection();
        if (!empty($creditMemos)) {
            foreach ($creditMemos as $creditMemo) {
                $total += $creditMemo->getGrandTotal();
            }
        }

        return $total;
    }

    /**
     * Get a parent transaction.
     */
    public function getParentTransaction($transactionType)
    {
        $parentTransaction = $this->hasTransaction($transactionType);
        return isset($parentTransaction[0]) ? $parentTransaction[0] : null;
    }

    /**
     * Set the current order status.
     */
    public function setOrderStatus($status, $state = null)
    {
        // Set the order state
        if ($state && !empty($state)) {
            $this->order->setState(
                $this->config->getValue($state)
            );
        }

        // Set the order status
        $this->order->setStatus(
            $this->config->getValue($status)
        );
    }

    /**
     * Add a transaction comment to an order.
     */
    public function addOrderComment($comment, $amount = null)
    {
        $amount = $amount ? $amount : $this->order->getGrandTotal();
        $this->payment->addTransactionCommentsToOrder(
            $this->transaction,
            __($comment, $this->formatAmount($amount))
        );
    }

    /**
     * Checks if an order has a transactions.
     */
    public function hasTransaction($transactionType, $order = null, $isClosed = 0)
    {
        // Prepare the order
        $order = $order ? $order : $this->order;

        // Get the auth transactions
        $transactions = $this->getTransactions(
            $transactionType,
            $order,
            $isClosed
        );

        return !empty($transactions) ? $transactions : false;
    }

    /**
     * Get the order amount.
     */
    public function formatAmount($amount)
    {
        return $this->order->getBaseCurrency()
            ->formatTxt($amount);
    }

    /**
     * Get the action id from a gateway response.
     */
    public function getActionId()
    {
        if (isset($this->paymentData['data']['action_id'])) {
            return $this->paymentData['data']['action_id'];
        } elseif (isset($this->paymentData['action_id'])) {
            return $this->paymentData['action_id'];
        }

        return null;
    }

    /**
     * Build a transaction.
     */
    public function buildTransaction()
    {
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
    }

    /**
     * Build a payment.
     */
    public function buildPayment()
    {
        return $this->order->getPayment()
            ->setMethod($this->methodId)
            ->setLastTransId($this->getActionId())
            ->setTransactionId($this->getActionId());
    }

    /**
     * Build a flat array from the gateway response.
     */
    public function buildDataArray($data)
    {
        // Prepare the fields to remove
        $remove = [
            '_links',
            'risk',
            'metadata',
            'customer',
            'source',
            'data'
        ];

        // Return the clean array
        return array_diff_key($data, array_flip($remove));
    }

    /**
     * Get transactions for an order.
     */
    public function getTransactions($transactionType = null, $order = null, $isClosed = 0)
    {
        // Prepare the order
        $order = $order ? $order : $this->order;

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
}
