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
     * @var Order
     */
    public $order;

    /**
     * @var Transaction
     */
    public $transaction;

    /**
     * @var Payment
     */
    public $payment;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
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
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
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
    }

    /**
     * Handle a webhook transaction.
     */
    public function handleTransaction($order, $webhook)
    {
        // Check if a transaction already exists
        $this->transaction = $this->hasTransaction(
            $order,
            $webhook['action_id']
        );

        $this->order = $order;
        $this->payment = $this->order->getPayment();

        // Load the webhook data
        $payload = json_decode($webhook['event_data']);

        // Format the amount
        $amount = $this->amountFromGateway(
            $payload->data->amount
        );

        // Check to see if webhook is supported
        if (isset(self::$transactionMapper[$webhook['event_type']])) {
            $isBackendAction = false;
            if (isset($payload->data->metadata->isBackendAction)) {
                $isBackendAction = $payload->data->metadata->isBackendAction;
            }

            // Create a transaction if needed
            if (!$this->transaction && !$isBackendAction) {
                // Build the transaction
                $this->buildTransaction(
                    $webhook,
                    $amount
                );

                // Add the order comment
                $this->addTransactionComment(
                    $amount
                );

                // Process the invoice case
                $this->processInvoice($amount);

                // Process the credit memo case
                $this->processCreditMemo($amount);
                
                // Process the order email case
                $this->processEmail();

                // Save
                $this->transaction->save();
                $this->payment->save();
                $this->order->save();
            } elseif ($this->transaction) {
                // Update the existing transaction state
                $this->transaction->setIsClosed(
                    $this->setTransactionState($amount)
                );

                // Process the order email case
                $this->processEmail();

                // Save
                $this->transaction->save();
                $this->payment->save();
                $this->order->save();
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
            ->addOrderIdFilter($orderId)
            ->getItems();

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
    public function buildTransaction($webhook, $amount)
    {
        // Prepare the data array
        $data = $this->utilities->objectToArray(
            json_decode($webhook['event_data'])
        );

        // Create the transaction
        $this->transaction = $this->transactionBuilder
            ->setPayment($this->payment)
            ->setOrder($this->order)
            ->setTransactionId($webhook['action_id'])
            ->setAdditionalInformation([
                Transaction::RAW_DETAILS => $this->buildDataArray($data)
            ])
            ->setFailSafe(true)
            ->build(self::$transactionMapper[$webhook['event_type']]);

        // Set the parent transaction id
        $this->transaction->setParentTxnId(
            $this->setParentTransactionId()
        );

        // Handle the transaction state
        $this->transaction->setIsClosed(
            $this->setTransactionState($amount)
        );
    }

    /**
     * Set a transaction parent id.
     */
    public function setParentTransactionId()
    {
        // Handle the void parent auth logic
        $isVoid = $this->transaction->getTxnType() == Transaction::TYPE_VOID;
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isVoid && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the capture parent auth logic
        $isCapture = $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isCapture && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the refund parent capture logic
        $isRefund = $this->transaction->getTxnType() == Transaction::TYPE_REFUND;
        $parentCapture = $this->getTransactionByType(
            Transaction::TYPE_CAPTURE
        );
        if ($isRefund && $parentCapture) {
            return $parentCapture->getTxnId();
        }

        return null;
    }

    /**
     * Set a transaction state.
     */
    public function setTransactionState($amount)
    {
        // Handle the first authorization transaction
        $noAuth = !$this->hasTransaction($this->order, $this->transaction->getTxnId());
        $isAuth = $this->transaction->getTxnType() == Transaction::TYPE_AUTH;
        if ($noAuth && $isAuth) {
            return 0;
        }

        // Handle a void after authorization
        $isVoid = $this->transaction->getTxnType() == Transaction::TYPE_VOID;
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isVoid && $parentAuth) {
            $parentAuth->setIsClosed(1)->save();
            return 1;
        }

        // Handle a capture after authorization
        $isCapture = $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $isPartialCapture = $this->isPartialCapture($amount, $isCapture);
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isPartialCapture && $parentAuth) {
            $parentAuth->setIsClosed(1)->save();
            return 0;
        } elseif ($isCapture && $parentAuth) {
            $parentAuth->setIsClosed(1)->save();
            return 0;
        }

        // Handle a refund after capture
        $isRefund = $this->transaction->getTxnType() == Transaction::TYPE_REFUND;
        $isPartialRefund = $this->isPartialRefund($amount, $isRefund);
        $parentCapture = $this->getTransactionByType(
            Transaction::TYPE_CAPTURE
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
    public function getTransactionByType($transactionType)
    {
        // Payment filter
        $filter1 = $this->filterBuilder
            ->setField('payment_id')
            ->setValue($this->order->getPayment()->getId())
            ->create();

        // Order filter
        $filter2 = $this->filterBuilder
            ->setField('order_id')
            ->setValue($this->order->getId())
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

        return !empty(current($transactions)) ? current($transactions) : false;
    }

    /**
     * Add a transaction comment to an order.
     */
    public function addTransactionComment($amount)
    {

        // Get the transaction type
        $type = $this->transaction->getTxnType();

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

        // Convert currency amount to base amount
        $amount = $amount / $this->order->getBaseToOrderRate();
        // Add the transaction comment
        $this->payment->addTransactionCommentsToOrder(
            $this->transaction,
            __($comment, $this->getFormattedAmount($amount))
        );
    }

    /**
     * Convert a gateway to decimal value for processing.
     */
    public function amountFromGateway($amount, $order = null)
    {
        // Get the quote currency
        $currency = $order ? $order->getOrderCurrencyCode() : $this->order->getOrderCurrencyCode();

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
    public function processCreditMemo($amount)
    {
        // Process the credit memo
        $isRefund = $this->transaction->getTxnType() == Transaction::TYPE_REFUND;
        $hasCreditMemo = $this->orderHasCreditMemo();
        if ($isRefund && !$hasCreditMemo) {
            // Get the invoice
            $invoice = $this->invoiceHandler->getInvoice($this->order);
            $currentTotal = $this->getCreditMemosTotal();

            // Create a credit memo
            $creditMemo = $this->creditMemoFactory->createByOrder($this->order);
            $creditMemo->setBaseGrandTotal($amount/$this->order->getBaseToOrderRate());
            $creditMemo->setGrandTotal($amount);

            // Refund
            $this->creditMemoService->refund($creditMemo);

            // Remove the core core duplicate credit memo comment
            foreach ($this->order->getAllStatusHistory() as $orderComment) {
                $condition1 = $orderComment->getStatus() == 'closed';
                $condition2 = $orderComment->getEntityName() == 'creditmemo';
                if ($condition1 && $condition2) {
                    $orderComment->delete();
                }
            }

            // Update the refunded amount
            $this->order->setTotalRefunded($currentTotal + $amount);
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
     * Check if an order has a credit memo.
     */
    public function orderHasCreditMemo()
    {
        // Loop through the items
        $result = 0;
        $creditMemos = $this->order->getCreditmemosCollection();
        if (!empty($creditMemos)) {
            foreach ($creditMemos as $creditMemo) {
                if ($creditMemo->getTransactionId() == $this->transaction->getTxnId()) {
                    $result++;
                }
            }
        }

        return $result > 0 ? true : false;
    }

    /**
     * Create an invoice for a captured transaction.
     */
    public function processInvoice($amount)
    {
        $isCapture = $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        if ($isCapture) {
            $this->invoiceHandler->createInvoice(
                $this->transaction,
                $amount
            );
        }
    }

    /**
     * Send the order email.
     */
    public function processEmail()
    {
        // Get the email sent flag
        $emailSent = $this->order->getEmailSent();

        // Prepare the authorization condition
        $condition1 = $this->config->getValue('order_email') == 'authorize'
            && $this->transaction->getTxnType() == Transaction::TYPE_AUTH && $emailSent == 0;

        // Prepare the capture condition
        $condition2 = $this->config->getValue('order_email') == 'authorize_capture'
            && $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE && $emailSent == 0;

        // Send the order email
        if ($condition1 || $condition2) {
            $this->order->setCanSendNewEmailFlag(true);
            $this->order->setIsCustomerNotified(true);
            $this->orderSender->send($this->order, true);
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
     * Format an amount with currency.
     */
    public function getFormattedAmount($amount)
    {
        return $this->order->getBaseCurrency()->formatTxt($amount);
    }

    /**
     * Check if a refund is partial.
     */
    public function isPartialRefund($amount, $isRefund, $order = null)
    {
        if ($order) {
            $this->order = $order;
        }
        
        // Get the total refunded
        $totalRefunded = $this->order->getTotalRefunded() + $amount;

        // Check the partial refund case
        $isPartialRefund = $this->order->getGrandTotal() > ($totalRefunded);

        return $isPartialRefund && $isRefund ? true : false;
    }

    /**
     * Check if a capture is partial.
     */
    public function isPartialCapture($amount, $isCapture)
    {
        // Get the total captured
        $totalCaptured = $this->order->getTotalInvoiced();

        // Check the partial capture case
        $isPartialCapture = $this->order->getGrandTotal() > ($totalCaptured + $amount);

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
}
