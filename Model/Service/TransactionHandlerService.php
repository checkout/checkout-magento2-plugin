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
     * @var Repository
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
     * @var Utilities
     */
    public $utilities;

    /**
     * @var InvoiceHandlerService
     */
    public $invoiceHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearch,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditMemoService,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Model\Service\InvoiceHandlerService $invoiceHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->transactionSearch     = $transactionSearch;
        $this->transactionBuilder    = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->creditMemoFactory     = $creditMemoFactory;
        $this->creditMemoService     = $creditMemoService;
        $this->utilities             = $utilities;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
    }

    /**
     * Generate transactions from webhooks.
     */
    public function webhooksToTransactions($order, $webhooks = [])
    {
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
        // Check if a transaction aleady exists
        $transaction = $this->hasTransaction(
            $order,
            $webhook['action_id']
        );

        // Create a transaction if needed
        if (!$transaction) {
            // Build the transaction
            $transaction = $this->buildTransaction($order, $webhook);

            // Load the webhook data
            $payload = json_decode($webhook['event_data']);

            // Format the amount
            $amount = $this->amountFromGateway(
                $payload->data->amount,
                $order
            );

            // Add the order comment
            $this->addTransactionComment(
                $transaction,
                $amount
            );

            // Process the credit memo case
            $this->processCreditMemo($transaction, $amount);

            // Process the invoice case
            $this->processInvoice($transaction, $amount);
        }

        // Update the order status
        $this->setOrderStatus($transaction);
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

        return isset($transaction[0]) ? $transaction[0] : false;  
    }

    /**
     * Create a transaction for an order.
     */
    public function buildTransaction($order, $webhook)
    {        
        // Get the order payment
        $payment = $order->getPayment();

        // Prepare the data array
        $data = $this->utilities->objectToArray(
            json_decode($webhook['event_data'])
        );

        // Create the transaction
        $transaction = $this->transactionBuilder
        ->setPayment($payment)
        ->setOrder($order)
        ->setTransactionId($webhook['action_id'])
        ->setAdditionalInformation([
            Transaction::RAW_DETAILS => $this->buildDataArray($data)
        ])
        ->setFailSafe(true)
        ->build(self::$transactionMapper[$webhook['event_type']]);

        // Set the parent transaction id
        $transaction->setParentTxnId(
            $this->setParentTransactionId($transaction)
        );

        // Handle the transaction state
        $transaction->setIsClosed(
            $this->setTransactionState($transaction)
        );

        // Save 
        $transaction->save();
        $payment->save();

        return $transaction;
    }

    /**
     * Set a transaction parent id.
     */
    public function setParentTransactionId($transaction)
    {
        // Get the order
        $order = $transaction->getOrder();

        // Handle the void parent auth logic
        $isVoid = $transaction->getTxnType() == Transaction::TYPE_VOID;
        $parentAuth = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_AUTH,
            $order->getPayment()->getId()
        );       
        if ($isVoid && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the capture parent auth logic
        $isCapture = $transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $parentAuth = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_AUTH,
            $order->getPayment()->getId()
        );       
        if ($isCapture && $parentAuth) {
            return $parentAuth->getTxnId();
        }
       
        // Handle the refund parent capture logic
        $isRefund = $transaction->getTxnType() == Transaction::TYPE_REFUND;
        $parentCapture = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_CAPTURE,
            $order->getPayment()->getId()
        );       
        if ($isRefund && $parentCapture) {
            return $parentCapture->getTxnId();
        }

        return null;
    }

    /**
     * Set a transaction state.
     */
    public function setTransactionState($transaction)
    {
        // Get the order
        $order = $transaction->getOrder();

        // Handle the first authorization transaction
        $noAuth = !$this->hasTransaction($order, $transaction->getTxnId());
        $isAuth = $transaction->getTxnType() == Transaction::TYPE_AUTH;
        if ($noAuth && $isAuth) {
            return 0;
        }

        // Handle a void after authorization
        $isVoid = $transaction->getTxnType() == Transaction::TYPE_VOID;
        $parentAuth = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_AUTH,
            $order->getPayment()->getId()
        );
        if ($isVoid && $parentAuth) {
            $parentAuth->close()->save();
            return 1;
        }

        // Handle a capture after authorization
        $isCapture = $transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $parentAuth = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_AUTH,
            $order->getPayment()->getId()
        );    
        if ($isCapture && $parentAuth) {
            $parentAuth->close()->save();
            return 0;
        }

        // Handle a refund after capture
        $isRefund = $transaction->getTxnType() == Transaction::TYPE_REFUND;
        $parentCapture = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_CAPTURE,
            $order->getPayment()->getId()
        );
        if ($isRefund && $parentCapture) {
            $parentCapture->close()->save();
            return 1;
        }  
    }

    /**
     * Set the current order status.
     */
    public function setOrderStatus($transaction)
    {
        // Get the order
        $order = $transaction->getOrder();

        // Get the transaction type
        $type = $transaction->getTxnType();

        // Set the default order state
        $state = null;

        // Get the needed order status
        switch ($type) {
            case Transaction::TYPE_AUTH:
                $status = 'order_status_authorized';
                break;

            case Transaction::TYPE_CAPTURE:
                $status = 'order_status_captured';
                break;

            case Transaction::TYPE_VOID:
                $status = 'order_status_voided';
                break;

            case Transaction::TYPE_REFUND:
                $status = 'order_status_refunded';
                break;
        }   

        // Set the order status
        $order->setStatus($this->config->getValue($status));

        // Save the order
        $order->save();
    }

    /**
     * Add a transaction comment to an order.
     */
    public function addTransactionComment($transaction, $amount)
    {
        // Get the order
        $order = $transaction->getOrder();
        
        // Get the order payment
        $payment = $order->getPayment();

        // Get the transaction type
        $type = $transaction->getTxnType();

        // Prepare the comment
        switch ($type) {
            case Transaction::TYPE_AUTH:
                $comment = 'The authorized amount is %1.';
                break;

            case Transaction::TYPE_CAPTURE:
                $comment = 'The captured amount is %1.';
                break;

            case Transaction::TYPE_VOID:
                $comment = 'The voided amount is %1.';
                break;

            case Transaction::TYPE_REFUND:
                $comment = 'The refunded amount is %1.';
                break;
        }   

        // Add the transaction comment
        $payment->addTransactionCommentsToOrder(
            $transaction,
            __($comment, $this->getFormattedAmount($order, $amount))
        );
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
     * Create a credit memo for a refunded transaction.
     */
    public function processCreditMemo($transaction, $amount)
    {
        // Process the credit memo
        $isRefund = $transaction->getTxnType() == Transaction::TYPE_REFUND;
        if ($isRefund) {
            // Get the order
            $order = $transaction->getOrder();

            // Get the invoice
            $invoice = $this->invoiceHandler->getInvoice($order);

            // Create a credit memo
            $creditMemo = $this->creditMemoFactory->createByOrder($order);
            $creditMemo->setBaseGrandTotal($amount);
            $creditMemo->setGrandTotal($amount);

            // Refund
            $this->creditMemoService->refund($creditMemo);

            // Remove the core core duplicate credit memo comment
            foreach ($order->getAllStatusHistory() as $orderComment) {
                $condition1 = $orderComment->getStatus() == 'closed';
                $condition2 = $orderComment->getEntityName() == 'creditmemo';
                if ($condition1 && $condition2) {
                    $orderComment->delete();
                }
            } 
        }
    }

    /**
     * Create an invoice for a captured transaction.
     */
    public function processInvoice($transaction, $amount)
    {
        $isCapture = $transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        if ($isCapture) {
            $this->invoiceHandler->createInvoice(
                $transaction,
                $amount
            );
        }
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
     * Format an amount with curerency.
     */
    public function getFormattedAmount($order, $amount)
    {
        return $order->getBaseCurrency()->formatTxt($amount);
    }
}
