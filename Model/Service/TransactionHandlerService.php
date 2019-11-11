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
use Magento\Payment\Model\MethodInterface;

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
     * @var ManagerInterface
     */
    public $messageManager;

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
     * @var OrderSender
     */
    private $orderSender;

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
     * @var Logger
     */
    public $logger;

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
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
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
        $this->orderSender           = $orderSender;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
        $this->utilities             = $utilities;
        $this->logger                = $logger;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $transactionType, $data = null, $isWebhook = true)
    {
        // Prepare the needed elements
        $this->prepareData($order, $transactionType, $data, $isWebhook);

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
        $this->order = $this->invoiceHandler->processInvoice(
            $this->order,
            $this->transaction
        );

        // Save the processed elements
        $this->saveData();

        // Return the order
        return $this->order;
    }

    /**
     * Prepare the required instance properties.
     */
    public function prepareData($order, $transactionType, $data, $isWebhook)
    {
        // Assign the request type
        $this->isWebhook = $isWebhook;

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

        return $this;
    }

    /**
     * Process an authorization request.
     */
    public function handleAuthorization()
    {
        $authTransaction = $this->hasTransaction(
            Transaction::TYPE_AUTH,
            $this->order
        );
        if (!$authTransaction) {
            // Set the order status
            $this->setOrderStatus(
                'order_status_authorized',
                Order::STATE_PENDING_PAYMENT
            );

            // Add order comment
            $this->addOrderComment('The authorized amount is %1.');

            // Set the parent transaction id
            $this->transaction->setParentTxnId(null);

            // Allow void
            $this->transaction->setIsClosed(0);

            // Check the email sender
            if ($this->config->getValue('order_email') == MethodInterface::ACTION_AUTHORIZE) {
                $order->setCanSendNewEmailFlag(true);
                $this->orderSender->send($this->order, true);
            }
        }
    }

    /**
     * Process a capture request.
     */
    public function handleCapture()
    {
        // Get the parent transaction
        $parentTransaction = $this->getParentTransaction(Transaction::TYPE_AUTH);

        // Handle the capture logic
        if ($parentTransaction) {
            $parentTransaction->close();
            $this->transaction->setParentTxnId(
                $parentTransaction->getTxnId()
            );
        
            // Set the order status
            $this->setOrderStatus(
                'order_status_captured',
                Order::STATE_PROCESSING
            );

            // Add order comment
            $this->addOrderComment('The captured amount is %1.');

            // Set the total paid
            $this->order->setTotalPaid($this->order->getGrandTotal());

            // Allow refund
            $this->transaction->setIsClosed(0);

            // Check the email sender
            if ($this->config->getValue('order_email') == MethodInterface::ACTION_AUTHORIZE_CAPTURE) {
                $this->order->setCanSendNewEmailFlag(true);
                $this->orderSender->send($this->order, true);
            }
        }
    }

    /**
     * Process a void request.
     */
    public function handleVoid()
    {
        $parentTransaction = $this->getParentTransaction(Transaction::TYPE_AUTH);
        if ($parentTransaction) {
            // Set the parent transaction id
            $parentTransaction->close();
            $this->transaction->setParentTxnId(
                $parentTransaction->getTxnId()
            );

            // Add order comment
            $this->addOrderComment('The voided amount is %1.');

            // Lock the transaction
            $this->transaction->setIsClosed(1);
        }

        // Set the order status
        $this->setOrderStatus(
            'order_status_voided',
            'order_status_voided'
        );

    }

    /**
     * Process a refund request.
     */
    public function handleRefund()
    {
        $parentTransaction = $this->getParentTransaction(Transaction::TYPE_CAPTURE);
        if ($parentTransaction) {
            // Set the parent transaction id
            $parentTransaction->close();
            $this->transaction->setParentTxnId(
                $parentTransaction->getTxnId()
            );
            
            // Prepare the refunded amount
            $amount = $this->paymentData['data']['amount']/100;

            // Load the invoice
            $invoice = $this->invoiceHandler->getInvoice($this->order);

            // Create a credit memo
            $creditMemo = $this->creditMemoFactory->createByOrder($this->order);
            $creditMemo->setInvoice($invoice);
            $creditMemo->setBaseGrandTotal($amount);

            // Update the refunded amount
            $this->order->setTotalRefunded($amount + $this->order->getTotalRefunded());

            // Refund
            $this->creditMemoService->refund($creditMemo);

            // Lock the transaction
            $this->transaction->setIsClosed(1);

            // Apply the order status
            if ($this->order->getGrandTotal() == $this->order->getTotalRefunded()) {
                $this->setOrderStatus(
                    'order_status_refunded',
                    'order_status_refunded'
                );
            }
            else {
                $this->setOrderStatus(
                    'order_status_refunded_partial',
                    'order_status_refunded_partial'
                );
            }
        }
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
    public function addOrderComment($comment)
    {
        $this->payment->addTransactionCommentsToOrder(
            $this->transaction,
            __($comment, $this->getAmount())
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
     * Get the order method ID.
     */
    public function getMethodId()
    {
        return $this->order->getPayment()
            ->getMethodInstance()
            ->getCode();
    }

    /**
     * Get the order amount.
     */
    public function getAmount()
    {
        return $this->order->getBaseCurrency()
            ->formatTxt(
                $this->order->getGrandTotal()
            );
    }
    
    /**
     * Save the required data.
     */
    public function saveData()
    {
        $this->payment->save();
        $this->transaction->save();
        $this->order->save();
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
        if ($transactionType && !empty($transactions)) {
            $filteredResult = [];
            foreach ($transactions as $transaction) {
                if ($transaction->getTxnType() == $transactionType && $transaction->getIsClosed() == $isClosed) {
                    $filteredResult[] = $transaction;
                }
            }

            return $filteredResult;
        }

        return $transactions;
    }
}
