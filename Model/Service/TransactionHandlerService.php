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

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;

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
     * @var \Magento\Sales\Model\Order
     */
    public $orderModel;

    /**
     * @var OrderSender
     */
    public $orderSender;

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
     * @var FilterBuilder
     */
    public $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

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
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearch,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditMemoService,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Model\Service\InvoiceHandlerService $invoiceHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler
    ) {
        $this->orderModel            = $orderModel;
        $this->orderSender           = $orderSender;
        $this->transactionSearch     = $transactionSearch;
        $this->transactionBuilder    = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->creditMemoFactory     = $creditMemoFactory;
        $this->creditMemoService     = $creditMemoService;
        $this->filterBuilder         = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->utilities             = $utilities;
        $this->invoiceHandler        = $invoiceHandler;
        $this->config                = $config;
        $this->orderHandler          = $orderHandler;
    }

    /**
     * Handle a webhook transaction.
     */
    public function handleTransaction($order, $webhook)
    {

        //Initialise $transaction
        $transaction = null;

        // Load the webhook data
        $payload = json_decode($webhook['event_data']);

        // Format the amount
        $amount = $this->amountFromGateway(
            $payload->data->amount,
            $order
        );

        if($this->needsTransaction($payload)) {
            // Check if a transaction aleady exists
            $transaction = $this->hasTransaction(
                $order,
                $webhook['action_id']
            );
            // Create a transaction if needed
            if (!$transaction) {
                // Build the transaction
                $transaction = $this->buildTransaction(
                    $order,
                    $webhook,
                    $amount
                );

                $eventData = json_decode($webhook['event_data']);
                $isBackendCapture = false;
                if (isset($eventData->data->metadata->isBackendCapture)) {
                    $isBackendCapture = $eventData->data->metadata->isBackendCapture;
                }
                if (!$isBackendCapture) {
                    // Add the order comment
                    $this->addTransactionComment(
                        $transaction,
                        $amount
                    );

                    // Process the invoice case
                    $this->processInvoice($transaction, $amount);
                }
            } else {
                // Get the payment
                $payment = $transaction->getOrder()->getPayment();

                // Update the existing transaction state
                $transaction->setIsClosed(
                    $this->setTransactionState($transaction, $amount)
                );

                // Save
                $transaction->save();
                $payment->save();
            }

            // Process the credit memo case
            $this->processCreditMemo($transaction, $amount);

            // Process the order email case
            $this->processEmail($transaction);
        }

        // Update the order status
        $this->setOrderStatus($transaction, $amount, $payload);
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
    public function buildTransaction($order, $webhook, $amount)
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
            $this->setTransactionState($transaction, $amount)
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
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH,
            $order
        );
        if ($isVoid && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the capture parent auth logic
        $isCapture = $transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH,
            $order
        );
        if ($isCapture && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the refund parent capture logic
        $isRefund = $transaction->getTxnType() == Transaction::TYPE_REFUND;
        $parentCapture = $this->getTransactionByType(
            Transaction::TYPE_CAPTURE,
            $order
        );
        if ($isRefund && $parentCapture) {
            return $parentCapture->getTxnId();
        }

        return null;
    }

    /**
     * Set a transaction state.
     */
    public function setTransactionState($transaction, $amount)
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
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH,
            $order
        );
        if ($isVoid && $parentAuth) {
            $parentAuth->setIsClosed(1)->save();
            return 1;
        }

        // Handle a capture after authorization
        $isCapture = $transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $isPartialCapture = $this->isPartialCapture($transaction, $amount, $isCapture);
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH,
            $order
        );
        if ($isPartialCapture && $parentAuth) {
            $parentAuth->setIsClosed(1)->save();
            return 0;
        } elseif ($isCapture && $parentAuth) {
            $parentAuth->setIsClosed(1)->save();
            return 0;
        }

        // Handle a refund after capture
        $isRefund = $transaction->getTxnType() == Transaction::TYPE_REFUND;
        $isPartialRefund = $this->isPartialRefund($transaction, $amount, $isRefund);
        $parentCapture = $this->getTransactionByType(
            Transaction::TYPE_CAPTURE,
            $order
        );
        if ($isPartialRefund && $parentCapture) {
            $parentCapture->setIsClosed(0)->save();
            return 1;
        } elseif ($isRefund && $parentCapture) {
            $parentCapture->setIsClosed(1)->save();
            return 1;
        }

        return 0;
    }

    /**
     * Get transactions for an order.
     */
    public function getTransactionByType($transactionType, $order)
    {
        // Payment filter
        $filter1 = $this->filterBuilder
            ->setField('payment_id')
            ->setValue($order->getPayment()->getId())
            ->create();

        // Order filter
        $filter2 = $this->filterBuilder
            ->setField('order_id')
            ->setValue($order->getId())
            ->create();

        // Type filter
        $filter3 = $this->filterBuilder
            ->setField('txn_type')
            ->setValue($transactionType)
            ->create();

        // Build the search criteria
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$filter1])
            ->addFilters([$filter2])
            ->addFilters([$filter3])
            ->setPageSize(1)
            ->create();

        // Get the list of transactions
        $transactions = $this->transactionRepository
        ->getList($searchCriteria)
        ->getItems();

        return isset($transactions[0]) ? $transactions[0] : false;
    }

    /**
     * Set the current order status.
     */
    public function setOrderStatus($transaction, $amount, $payload)
    {
        // Get the order
        $order = $transaction->getOrder();
        // Get the event type
        $type = $transaction->getTxnType();

        // Initialise state and status
        $state = null;
        $status = null;

        // Get the needed order status
        switch ($type) {
            case Transaction::TYPE_AUTH:
                if ($order->getState() !== 'processing') {
                    $status = $this->config->getValue('order_status_authorized');
                }
                // Flag order if potential fraud
                if ($this->isFlagged($payload)) {
                    $status = $this->config->getValue('order_status_flagged');
                }
                break;

            case Transaction::TYPE_CAPTURE:
                $status = $this->config->getValue('order_status_captured');
                $state = $this->orderModel::STATE_PROCESSING;
                break;

            case Transaction::TYPE_VOID:
                $status = $this->config->getValue('order_status_voided');
                $state = $this->orderModel::STATE_CANCELED;
                break;

            case Transaction::TYPE_REFUND:
                $isPartialRefund = $this->isPartialRefund(
                    $transaction,
                    $amount,
                    true
                );
                $status = $isPartialRefund ? 'order_status_captured' : 'order_status_refunded';
                $status = $this->config->getValue($status);
                $state = $isPartialRefund ? $this->orderModel::STATE_PROCESSING : $this->orderModel::STATE_CLOSED;
                break;

            case 'payment_capture_pending':
                if (isset($payload->data->metadata->methodId)
                    && $payload->data->metadata->methodId === 'checkoutcom_apm'
                ) {
                    $state = $this->orderModel::STATE_PENDING_PAYMENT;
                    $status = $order->getConfig()->getStateDefaultStatus($state);
                    $order->addStatusHistoryComment(__('Payment capture initiated, awaiting capture confirmation.'));
                }
                break;

            case 'payment_expired':
                $this->orderHandler->handleFailedPayment($order);
                break;
        }

        if ($state) {
            // Set the order state
            $order->setState($state);
        }

        if ($status) {
            // Set the order status
            $order->setStatus($status);
        }

        // Check if order has not been deleted
        if ($this->orderHandler->isOrder($order)) {
            // Save the order
            $order->save();
        }
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
        // Get the order
        $order = $transaction->getOrder();

        // Get the order payment
        $payment = $order->getPayment();

        // Process the credit memo
        $isRefund = $transaction->getTxnType() == Transaction::TYPE_REFUND;
        $hasCreditMemo = $this->orderHasCreditMemo($transaction);
        if ($isRefund && !$hasCreditMemo) {
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

            // Update the refunded amount
            $order->setTotalRefunded($this->getCreditMemosTotal($order));

            // Save the data
            $payment->save();
            $transaction->save();
            $order->save();
        }
    }

    /**
     * Get the total credit memos amount.
     */
    public function getCreditMemosTotal($order)
    {
        $total = 0;
        $creditMemos = $order->getCreditmemosCollection();
        if (!empty($creditMemos)) {
            foreach ($creditMemos as $creditMemo) {
                $total += $creditMemo->getGrandTotal();
            }
        }

        return $total;
    }

    /**
     * Check if an order has a credit memo.
     */
    public function orderHasCreditMemo($transaction)
    {
        // Get the order
        $order = $transaction->getOrder();

        // Loop through the items
        $result = 0;
        $creditMemos = $order->getCreditmemosCollection();
        if (!empty($creditMemos)) {
            foreach ($creditMemos as $creditMemo) {
                if ($creditMemo->getTransactionId() == $transaction->getTxnId()) {
                    $result++;
                }
            }
        }

        return $result > 0 ? true : false;
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
     * Send the order email.
     */
    public function processEmail($transaction)
    {
        // Get the transaction type
        $type = $transaction->getTxnType();

        // Get the order
        $order = $transaction->getOrder();

        // Get the email sent flag
        $emailSent = $order->getEmailSent();

        // Prepare the authorization condition
        $condition1 = $this->config->getValue('order_email') == 'authorize'
        && $transaction->getTxnType() == Transaction::TYPE_AUTH && $emailSent == 0;

        // Prepare the capture condition
        $condition2 = $this->config->getValue('order_email') == 'authorize_capture'
        && $transaction->getTxnType() == Transaction::TYPE_CAPTURE && $emailSent == 0;

        // Send the order email
        if ($condition1 || $condition2) {
            $order->setCanSendNewEmailFlag(true);
            $order->setIsCustomerNotified(true);
            $this->orderSender->send($order, true);
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

    /**
     * Check if a refund is partial.
     */
    public function isPartialRefund($transaction, $amount, $isRefund)
    {
        // Get the order
        $order = $transaction->getOrder();

        // Get the total refunded
        $totalRefunded = $order->getTotalRefunded();

        // Check the partial refund case
        $isPartialRefund = $order->getGrandTotal() > ($totalRefunded + $amount);

        return $isPartialRefund && $isRefund ? true : false;
    }

    /**
     * Check if a capture is partial.
     */
    public function isPartialCapture($transaction, $amount, $isCapture)
    {
        // Get the order
        $order = $transaction->getOrder();

        // Get the total captured
        $totalCaptured = $order->getTotalInvoiced();

        // Check the partial capture case
        $isPartialCapture = $order->getGrandTotal() > ($totalCaptured + $amount);

        return $isPartialCapture && $isCapture ? true : false;
    }

    /**
     * @param $payload
     * @return bool
     * Check if payment has been flagged for potential fraud
     */
    public function isFlagged($payload) {
        return isset($payload->data->risk->flagged)
            && $payload->data->risk->flagged == true;
    }

    public function needsTransaction($payload)
    {
        return isset(self::$transactionMapper[$payload->type]);
    }
}
