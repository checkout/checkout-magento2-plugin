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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use Exception;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Convert\Order as OrderConvertor;
use Magento\Sales\Model\Convert\OrderFactory as ConvertorFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\Service\CreditmemoService;

/**
 * Class TransactionHandlerService
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class TransactionHandlerService
{
    /**
     * $transactionMapper field
     *
     * @var array $transactionMapper
     */
    public static $transactionMapper = [
        'payment_approved' => Transaction::TYPE_AUTH,
        'payment_captured' => Transaction::TYPE_CAPTURE,
        'payment_refunded' => Transaction::TYPE_REFUND,
        'payment_voided'   => Transaction::TYPE_VOID,
    ];
    /**
     * $orderModel field
     *
     * @var Order $orderModel
     */
    public $orderModel;
    /**
     * $orderSender field
     *
     * @var OrderSender $orderSender
     */
    public $orderSender;
    /**
     * $transactionSearch field
     *
     * @var TransactionSearchResultInterfaceFactory $transactionSearch
     */
    public $transactionSearch;
    /**
     * $transactionBuilder field
     *
     * @var BuilderInterface $transactionBuilder
     */
    public $transactionBuilder;
    /**
     * $transactionRepository field
     *
     * @var Repository $transactionRepository
     */
    public $transactionRepository;
    /**
     * $creditMemoFactory field
     *
     * @var CreditmemoFactory $creditMemoFactory
     */
    public $creditMemoFactory;
    /**
     * $creditMemoService field
     *
     * @var CreditmemoService $creditMemoService
     */
    public $creditMemoService;
    /**
     * $filterBuilder field
     *
     * @var FilterBuilder $filterBuilder
     */
    public $filterBuilder;
    /**
     * $searchCriteriaBuilder field
     *
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public $searchCriteriaBuilder;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    public $utilities;
    /**
     * $invoiceHandler field
     *
     * @var InvoiceHandlerService $invoiceHandler
     */
    public $invoiceHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $orderManagement field
     *
     * @var OrderManagementInterface $orderManagement
     */
    public $orderManagement;
    /**
     * $order field
     *
     * @var Order $order
     */
    public $order;
    /**
     * $transaction field
     *
     * @var Transaction $transaction
     */
    public $transaction;
    /**
     * $payment field
     *
     * @var Payment $payment
     */
    public $payment;
    /**
     * Order convert object.
     *
     * @var ConvertorFactory
     */
    protected $convertorFactory;
    /**
     * $orderPaymentRepository field
     *
     * @var OrderPaymentRepositoryInterface $orderPaymentRepository
     */
    private $orderPaymentRepository;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;
    /**
     * $orderStatusHistoryRepository field
     *
     * @var OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    private $orderStatusHistoryRepository;

    /**
     * TransactionHandlerService constructor
     *
     * @param OrderSender                             $orderSender
     * @param TransactionSearchResultInterfaceFactory $transactionSearch
     * @param BuilderInterface                        $transactionBuilder
     * @param Repository                              $transactionRepository
     * @param CreditmemoFactory                       $creditMemoFactory
     * @param CreditmemoService                       $creditMemoService
     * @param FilterBuilder                           $filterBuilder
     * @param SearchCriteriaBuilder                   $searchCriteriaBuilder
     * @param Utilities                               $utilities
     * @param InvoiceHandlerService                   $invoiceHandler
     * @param Config                                  $config
     * @param OrderManagementInterface                $orderManagement
     * @param Order                                   $orderModel
     * @param ConvertorFactory                        $convertOrderFactory
     * @param OrderPaymentRepositoryInterface         $orderPaymentRepository
     * @param OrderRepositoryInterface                $orderRepository
     * @param OrderStatusHistoryRepositoryInterface   $orderStatusHistoryRepository
     */
    public function __construct(
        OrderSender $orderSender,
        TransactionSearchResultInterfaceFactory $transactionSearch,
        BuilderInterface $transactionBuilder,
        Repository $transactionRepository,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoService $creditMemoService,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Utilities $utilities,
        InvoiceHandlerService $invoiceHandler,
        Config $config,
        OrderManagementInterface $orderManagement,
        Order $orderModel,
        ConvertorFactory $convertOrderFactory,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->orderSender                  = $orderSender;
        $this->transactionSearch            = $transactionSearch;
        $this->transactionBuilder           = $transactionBuilder;
        $this->transactionRepository        = $transactionRepository;
        $this->creditMemoFactory            = $creditMemoFactory;
        $this->creditMemoService            = $creditMemoService;
        $this->filterBuilder                = $filterBuilder;
        $this->searchCriteriaBuilder        = $searchCriteriaBuilder;
        $this->utilities                    = $utilities;
        $this->invoiceHandler               = $invoiceHandler;
        $this->config                       = $config;
        $this->orderManagement              = $orderManagement;
        $this->orderModel                   = $orderModel;
        $this->convertorFactory             = $convertOrderFactory;
        $this->orderPaymentRepository       = $orderPaymentRepository;
        $this->orderRepository              = $orderRepository;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * Handle a webhook transaction
     *
     * @param $order
     * @param $webhook
     *
     * @return void
     * @throws Exception
     */
    public function handleTransaction($order, $webhook)
    {
        // Check if a transaction already exists
        $this->transaction = $this->hasTransaction(
            $order,
            $webhook['action_id']
        );

        $this->order   = $order;
        $this->payment = $this->order->getPayment();

        // Load the webhook data
        $payload = json_decode($webhook['event_data']);

        // Format the amount
        $amount = $this->amountFromGateway(
            $payload->data->amount
        );

        // Check to see if webhook is supported
        if (isset(self::$transactionMapper[$webhook['event_type']])) {
            // Create a transaction if needed
            if (!$this->transaction) {
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
                $this->processEmail($payload);

                // Save
                $this->transactionRepository->save($this->transaction);
                $this->orderPaymentRepository->save($this->payment);
                $this->orderRepository->save($this->order);

                // Process the payment void case
                $this->processVoid();
            } else {
                // Update the existing transaction state
                $this->transaction->setIsClosed(
                    $this->setTransactionState($amount)
                );

                // Process the order email case
                $this->processEmail($payload);

                // Save
                $this->transactionRepository->save($this->transaction);
                $this->orderPaymentRepository->save($this->payment);
                $this->orderRepository->save($this->order);
            }
        }
    }

    /**
     * Get the transactions for an order
     *
     * @param      $orderId
     * @param null $transactionId
     *
     * @return array|DataObject[]|TransactionInterface[]
     */
    public function getTransactions($orderId, $transactionId = null)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();

        // Get the list of transactions
        $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();

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
     * Get the transactions for an order
     *
     * @param      $order
     * @param null $transactionId
     *
     * @return false|DataObject|TransactionInterface|mixed
     */
    public function hasTransaction($order, $transactionId = null)
    {
        $transaction = $this->getTransactions(
            $order->getId(),
            $transactionId
        );

        return isset($transaction[0]) ? $transaction[0] : false;
    }

    /**
     * Create a transaction for an order
     *
     * @param $webhook
     * @param $amount
     *
     * @return void
     * @throws LocalizedException
     */
    public function buildTransaction($webhook, $amount)
    {
        // Prepare the data array
        $data = $this->utilities->objectToArray(
            json_decode($webhook['event_data'])
        );

        // Create the transaction
        $this->transaction = $this->transactionBuilder->setPayment($this->payment)
            ->setOrder($this->order)
            ->setTransactionId($webhook['action_id'])
            ->setAdditionalInformation([
                Transaction::RAW_DETAILS => $this->buildDataArray($data),
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
     * Set a transaction parent id
     *
     * @return null
     */
    public function setParentTransactionId()
    {
        // Handle the void parent auth logic
        $isVoid     = $this->transaction->getTxnType() == Transaction::TYPE_VOID;
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isVoid && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the capture parent auth logic
        $isCapture  = $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isCapture && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the refund parent capture logic
        $isRefund      = $this->transaction->getTxnType() == Transaction::TYPE_REFUND;
        $parentCapture = $this->getTransactionByType(
            Transaction::TYPE_CAPTURE
        );
        if ($isRefund && $parentCapture) {
            return $parentCapture->getTxnId();
        }

        return null;
    }

    /**
     * Set a transaction state
     *
     * @param $amount
     *
     * @return int
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
        $isVoid     = $this->transaction->getTxnType() == Transaction::TYPE_VOID;
        $parentAuth = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isVoid && $parentAuth) {
            $parentAuth->setIsClosed(1);
            $this->transactionRepository->save($parentAuth);

            return 1;
        }

        // Handle a capture after authorization
        $isCapture        = $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE;
        $isPartialCapture = $this->isPartialCapture($amount, $isCapture);
        $parentAuth       = $this->getTransactionByType(
            Transaction::TYPE_AUTH
        );
        if ($isPartialCapture && $parentAuth) {
            $parentAuth->setIsClosed(1);
            $this->transactionRepository->save($parentAuth);

            return 0;
        } elseif ($isCapture && $parentAuth) {
            $parentAuth->setIsClosed(1);
            $this->transactionRepository->save($parentAuth);

            return 0;
        }

        // Handle a refund after capture
        $isRefund        = $this->transaction->getTxnType() == Transaction::TYPE_REFUND;
        $isPartialRefund = $this->isPartialRefund($amount, $isRefund);
        $parentCapture   = $this->getTransactionByType(
            Transaction::TYPE_CAPTURE
        );
        if ($isPartialRefund && $parentCapture) {
            $parentCapture->setIsClosed(0);
            $this->transactionRepository->save($parentCapture);

            return 1;
        } elseif ($isRefund && $parentCapture) {
            $parentCapture->setIsClosed(1);
            $this->transactionRepository->save($parentCapture);

            return 1;
        }

        return 0;
    }

    /**
     * Get transactions for an order
     *
     * @param      $transactionType
     * @param null $order
     *
     * @return false|mixed
     */
    public function getTransactionByType($transactionType, $order = null)
    {
        if ($order) {
            $this->order = $order;
        }

        // Payment filter
        $filter1 = $this->filterBuilder->setField('payment_id')->setValue($this->order->getPayment()->getId())->create(
        );

        // Order filter
        $filter2 = $this->filterBuilder->setField('order_id')->setValue($this->order->getId())->create();

        // Type filter
        $filter3 = $this->filterBuilder->setField('txn_type')->setValue($transactionType)->create();

        // Build the search criteria
        $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filter1])->addFilters([$filter2])->addFilters(
            [$filter3])->setPageSize(1)->create();

        // Get the list of transactions
        $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();

        return !empty(current($transactions)) ? current($transactions) : false;
    }

    /**
     * Add a transaction comment to an order
     *
     * @param $amount
     *
     * @return void
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

        // Add the transaction comment
        $this->payment->addTransactionCommentsToOrder(
            $this->transaction,
            __($comment, $this->getFormattedAmount($amount))
        );
    }

    /**
     * Convert a gateway to decimal value for processing
     *
     * @param      $amount
     * @param null $order
     *
     * @return float|int|mixed
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
            return $amount / 1000;
        } else {
            return $amount / 100;
        }
    }

    /**
     * Create a credit memo for a refunded transaction
     *
     * @param $amount
     *
     * @return void
     * @throws LocalizedException
     */
    public function processCreditMemo($amount)
    {
        // Process the credit memo
        $isRefund      = $this->transaction->getTxnType() == Transaction::TYPE_REFUND;
        $hasCreditMemo = $this->orderHasCreditMemo();
        if ($isRefund && !$hasCreditMemo) {
            $currentTotal = $this->getCreditMemosTotal();

            $isPartialRefund = $this->isPartialRefund(
                $amount,
                true,
                $this->order,
                false
            );

            // Create a credit memo
            if ($isPartialRefund) {
                /** @var OrderConvertor $convertor */
                $convertor  = $this->convertorFactory->create();
                $creditMemo = $convertor->toCreditmemo($this->order);
                $creditMemo->setAdjustmentPositive($amount);
                $creditMemo->setBaseShippingAmount(0);
                $creditMemo->collectTotals();
            } else {
                $creditMemoData = [
                    'adjustment_positive' => $amount,
                    'adjustment_negative' => $currentTotal + $amount,
                ];
                $creditMemo     = $this->creditMemoFactory->createByOrder($this->order, $creditMemoData);
            }

            // Update the order history comment status
            $orderComments = $this->order->getStatusHistories();
            $orderComment  = array_pop($orderComments);

            // Refund
            $this->creditMemoService->refund($creditMemo);

            $status = $isPartialRefund ? $this->config->getValue('order_status_refunded') : 'closed';
            $orderComment->setData('status', $status);
            $this->orderStatusHistoryRepository->save($orderComment);

            // Remove the core credit memo comment
            $orderComments = $this->order->getAllStatusHistory();
            foreach ($orderComments as $orderComment) {
                if ($orderComment->getEntityName() === 'creditmemo') {
                    $this->orderStatusHistoryRepository->delete($orderComment);
                }
            }

            // Amend the order status set by magento when refunding the credit memo
            $this->order->setStatus($status);
            $this->order->setTotalRefunded($currentTotal + $amount);
        }
    }

    /**
     * Get the total credit memos amount
     *
     * @return int
     */
    public function getCreditMemosTotal()
    {
        $total       = 0;
        $creditMemos = $this->order->getCreditmemosCollection();
        if (!empty($creditMemos)) {
            foreach ($creditMemos as $creditMemo) {
                $total += $creditMemo->getGrandTotal();
            }
        }

        return $total;
    }

    /**
     * Check if an order has a credit memo
     *
     * @return bool
     */
    public function orderHasCreditMemo()
    {
        // Loop through the items
        $result      = 0;
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
     * Create an invoice for a captured transaction
     *
     * @param $amount
     *
     * @return void
     * @throws LocalizedException
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
     * Send the order email
     *
     * @param $payload
     *
     * @return void
     */
    public function processEmail($payload)
    {
        // Get the email sent flag
        $emailSent = $this->order->getEmailSent();

        // Prepare the authorization condition
        $condition1 = $this->config->getValue('order_email') == 'authorize' && $this->transaction->getTxnType(
            ) == Transaction::TYPE_AUTH && $emailSent == 0;

        // Prepare the capture condition
        $condition2 = $this->config->getValue('order_email') == 'authorize_capture' && $this->transaction->getTxnType(
            ) == Transaction::TYPE_CAPTURE && $emailSent == 0;

        $condition3 = $this->config->getValue('order_email') == 'authorize' && $this->transaction->getTxnType(
            ) == Transaction::TYPE_CAPTURE && $payload->data->metadata->methodId == 'checkoutcom_apm' && $emailSent == 0;

        // Send the order email
        if ($condition1 || $condition2 || $condition3) {
            $this->order->setCanSendNewEmailFlag(true);
            $this->order->setIsCustomerNotified(true);
            $this->orderSender->send($this->order, true);
        }
    }

    /**
     * Cancel the order for a void transaction when void status is set to canceled
     *
     * @return void
     */
    public function processVoid()
    {
        $isVoid = $this->transaction->getTxnType() == Transaction::TYPE_VOID;
        if ($isVoid && $this->config->getValue('order_status_voided') == 'canceled') {
            $this->orderManagement->cancel($this->order->getEntityId());
        }
    }

    /**
     * Build a flat array from the gateway response
     *
     * @param $data
     *
     * @return array
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
            'data',
        ];

        // Return the clean array
        return array_diff_key($data, array_flip($remove));
    }

    /**
     * Format an amount with currency
     *
     * @param $amount
     *
     * @return string
     */
    public function getFormattedAmount($amount)
    {
        return $this->order->formatPriceTxt($amount);
    }

    /**
     * Check if a refund is partial
     *
     * @param       $amount
     * @param       $isRefund
     * @param null  $order
     * @param false $processed
     *
     * @return bool
     */
    public function isPartialRefund($amount, $isRefund, $order = null, $processed = false)
    {
        if ($order) {
            $this->order = $order;
        }

        // Get the total refunded
        $totalRefunded = $processed ? $this->order->getTotalRefunded() : $this->order->getTotalRefunded() + $amount;

        // Check the partial refund case
        $isPartialRefund = $this->order->getGrandTotal() > ($totalRefunded);

        return $isPartialRefund && $isRefund ? true : false;
    }

    /**
     * Check if a capture is partial
     *
     * @param $amount
     * @param $isCapture
     *
     * @return bool
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
     * Check if payment has been flagged for potential fraud
     *
     * @param $payload
     *
     * @return bool
     */
    public function isFlagged($payload)
    {
        return isset($payload->data->risk->flagged) && $payload->data->risk->flagged == true;
    }

    /**
     * Return transaction details for additional logging.
     *
     * @param $order
     *
     * @return array
     */
    public function getTransactionDetails($order)
    {
        $transactions = $this->getTransactions($order->getId());
        $items        = [];
        foreach ($transactions as $transaction) {
            $items[] = [
                'transaction_id'         => $transaction->getTxnId(),
                'payment_id'             => $transaction->getPaymentId(),
                'txn_type'               => $transaction->getTxnType(),
                'order_id'               => $transaction->getOrderId(),
                'is_closed'              => $transaction->getIsClosed(),
                'additional_information' => $transaction->getAdditionalInformation(),
                'created_at'             => $transaction->getCreatedAt(),
            ];
        }

        return $items;
    }
}
