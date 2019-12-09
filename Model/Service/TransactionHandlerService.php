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
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Model\Service\InvoiceHandlerService $invoiceHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->transactionSearch     = $transactionSearch;
        $this->transactionBuilder    = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->utilities             = $utilities;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
    }

    /**
     * Handle a webhook transaction.
     */
    public function handleTransaction($order, $webhook)
    {
        // Check if a transaction aleady exists
        $condition = $this->hasTransaction(
            $order,
            $webhook['action_id']
        );

        // Create a transaction if needed
        if (!$condition) {
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

            // Update the order status
            $this->setOrderStatus($transaction);

            // Process the invoice in needed
            $isCapture = $transaction->getTxnType() == Transaction::TYPE_CAPTURE;
            if ($isCapture) {
                $this->invoiceHandler->processInvoice(
                    $order,
                    $transaction,
                    $amount
                );
            }
        }
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

        return !empty($transaction) ? true : false;  
    }

    /**
     * Generate transactions from webhooks.
     */
    public function webhooksToTransactions($order, $webhooks = [])
    {
        // Create the transactions
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
        ->setAdditionalInformation(
            [
                Transaction::RAW_DETAILS => $this->buildDataArray($data)
            ]
        )
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

        // Handle the capture's parent auth logic
        $isCapture = $transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $parentAuth = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_AUTH,
            $order->getPayment()->getId()
        );       
        if ($isCapture && $parentAuth) {
            return $parentAuth->getTxnId();
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
                $state = 'order_status_voided';
                break;

            case Transaction::TYPE_REFUND:
                $status = 'order_status_refunded';
                break;
        }   

        // Set the order status
        $order->setStatus($this->config->getValue($status));

        // Set the order state
        if ($state) {
            $order->setState($state);
        }

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
