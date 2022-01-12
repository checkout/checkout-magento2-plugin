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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use Exception;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
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
 */
class TransactionHandlerService
{
    /**
     * TRANSACTION_MAPPER const
     *
     * @var array TRANSACTION_MAPPER
     */
    const TRANSACTION_MAPPER = [
        'payment_approved' => TransactionInterface::TYPE_AUTH,
        'payment_captured' => TransactionInterface::TYPE_CAPTURE,
        'payment_refunded' => TransactionInterface::TYPE_REFUND,
        'payment_voided'   => TransactionInterface::TYPE_VOID,
    ];
    /**
     * $orderSender field
     *
     * @var OrderSender $orderSender
     */
    private $orderSender;
    /**
     * $transactionBuilder field
     *
     * @var BuilderInterface $transactionBuilder
     */
    private $transactionBuilder;
    /**
     * $transactionRepository field
     *
     * @var Repository $transactionRepository
     */
    private $transactionRepository;
    /**
     * $creditMemoFactory field
     *
     * @var CreditmemoFactory $creditMemoFactory
     */
    private $creditMemoFactory;
    /**
     * $creditMemoService field
     *
     * @var CreditmemoService $creditMemoService
     */
    private $creditMemoService;
    /**
     * $filterBuilder field
     *
     * @var FilterBuilder $filterBuilder
     */
    private $filterBuilder;
    /**
     * $searchCriteriaBuilder field
     *
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $invoiceHandler field
     *
     * @var InvoiceHandlerService $invoiceHandler
     */
    private $invoiceHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $orderManagement field
     *
     * @var OrderManagementInterface $orderManagement
     */
    private $orderManagement;
    /**
     * $order field
     *
     * @var Order $order
     */
    private $order;
    /**
     * $transaction field
     *
     * @var Transaction $transaction
     */
    private $transaction;
    /**
     * $payment field
     *
     * @var Order\Payment $payment
     */
    private $payment;
    /**
     * Order convert object.
     *
     * @var ConvertorFactory
     */
    private $convertorFactory;
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
     * @param ConvertorFactory                        $convertOrderFactory
     * @param OrderPaymentRepositoryInterface         $orderPaymentRepository
     * @param OrderRepositoryInterface                $orderRepository
     * @param OrderStatusHistoryRepositoryInterface   $orderStatusHistoryRepository
     */
    public function __construct(
        OrderSender $orderSender,
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
        ConvertorFactory $convertOrderFactory,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->orderSender                  = $orderSender;
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
        $this->convertorFactory             = $convertOrderFactory;
        $this->orderPaymentRepository       = $orderPaymentRepository;
        $this->orderRepository              = $orderRepository;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * Handle a webhook transaction
     *
     * @param OrderInterface $order
     * @param mixed[]        $webhook
     *
     * @return void
     * @throws Exception
     */
    public function handleTransaction(OrderInterface $order, array $webhook): void
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
        if (isset(self::TRANSACTION_MAPPER[$webhook['event_type']])) {
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
     * @param mixed       $orderId
     * @param string|null $transactionId
     *
     * @return TransactionInterface[]
     */
    public function getTransactions($orderId, string $transactionId = null): array
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
     * @param OrderInterface $order
     * @param string|null    $transactionId
     *
     * @return false|TransactionInterface
     */
    public function hasTransaction(OrderInterface $order, string $transactionId = null)
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
     * @param mixed[] $webhook
     * @param float   $amount
     *
     * @return void
     * @throws LocalizedException
     */
    public function buildTransaction(array $webhook, float $amount): void
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
            ->build(self::TRANSACTION_MAPPER[$webhook['event_type']]);

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
     * @return string|int|null
     */
    public function setParentTransactionId()
    {
        // Handle the void parent auth logic
        $isVoid     = $this->transaction->getTxnType() === TransactionInterface::TYPE_VOID;
        $parentAuth = $this->getTransactionByType(
            TransactionInterface::TYPE_AUTH
        );
        if ($isVoid && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the capture parent auth logic
        $isCapture  = $this->transaction->getTxnType() === TransactionInterface::TYPE_CAPTURE;
        $parentAuth = $this->getTransactionByType(
            TransactionInterface::TYPE_AUTH
        );
        if ($isCapture && $parentAuth) {
            return $parentAuth->getTxnId();
        }

        // Handle the refund parent capture logic
        $isRefund      = $this->transaction->getTxnType() === TransactionInterface::TYPE_REFUND;
        $parentCapture = $this->getTransactionByType(
            TransactionInterface::TYPE_CAPTURE
        );
        if ($isRefund && $parentCapture) {
            return $parentCapture->getTxnId();
        }

        return null;
    }

    /**
     * Set a transaction state
     *
     * @param float $amount
     *
     * @return int
     */
    public function setTransactionState(float $amount): int
    {
        // Handle the first authorization transaction
        $noAuth = !$this->hasTransaction($this->order, $this->transaction->getTxnId());
        $isAuth = $this->transaction->getTxnType() === TransactionInterface::TYPE_AUTH;
        if ($noAuth && $isAuth) {
            return 0;
        }

        // Handle a void after authorization
        $isVoid     = $this->transaction->getTxnType() === TransactionInterface::TYPE_VOID;
        $parentAuth = $this->getTransactionByType(
            TransactionInterface::TYPE_AUTH
        );
        if ($isVoid && $parentAuth) {
            $parentAuth->setIsClosed(1);
            $this->transactionRepository->save($parentAuth);

            return 1;
        }

        // Handle a capture after authorization
        $isCapture        = $this->transaction->getTxnType() === Transaction::TYPE_CAPTURE;
        $isPartialCapture = $this->isPartialCapture($amount, $isCapture);
        $parentAuth       = $this->getTransactionByType(
            TransactionInterface::TYPE_AUTH
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
        $isRefund        = $this->transaction->getTxnType() === Transaction::TYPE_REFUND;
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
     * @param string               $transactionType
     * @param OrderInterface|null  $order
     *
     * @return TransactionInterface[]|false
     */
    public function getTransactionByType(string $transactionType, OrderInterface $order = null)
    {
        if ($order) {
            $this->order = $order;
        }

        // Payment filter
        $filter1 = $this->filterBuilder->setField('payment_id')->setValue($this->order->getPayment()->getId())->create();

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
     * @param float $amount
     *
     * @return void
     */
    public function addTransactionComment(float $amount): void
    {
        // Get the transaction type
        $type = $this->transaction->getTxnType();

        // Prepare the comment
        switch ($type) {
            case TransactionInterface::TYPE_AUTH:
                $comment = 'The authorized amount is %1.';
                break;

            case TransactionInterface::TYPE_CAPTURE:
                $comment = 'The captured amount is %1.';
                break;

            case TransactionInterface::TYPE_VOID:
                $comment = 'The voided amount is %1.';
                break;

            case TransactionInterface::TYPE_REFUND:
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
     * @param float               $amount
     * @param OrderInterface|null $order
     *
     * @return float|int|mixed
     */
    public function amountFromGateway(float $amount, OrderInterface $order = null)
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
     * @param float $amount
     *
     * @return void
     * @throws LocalizedException
     */
    public function processCreditMemo(float $amount): void
    {
        // Process the credit memo
        $isRefund      = $this->transaction->getTxnType() === TransactionInterface::TYPE_REFUND;
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
     * @return int|float
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
    public function orderHasCreditMemo(): bool
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

        return $result > 0;
    }

    /**
     * Create an invoice for a captured transaction
     *
     * @param float $amount
     *
     * @return void
     * @throws LocalizedException
     */
    public function processInvoice(float $amount): void
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
     * @param mixed $payload
     *
     * @return void
     */
    public function processEmail($payload): void
    {
        // Get the email sent flag
        $emailSent = $this->order->getEmailSent();

        // Prepare the authorization condition
        $condition1 = $this->config->getValue('order_email') === 'authorize' && $this->transaction->getTxnType(
            ) === TransactionInterface::TYPE_AUTH && $emailSent == 0;

        // Prepare the capture condition
        $condition2 = $this->config->getValue('order_email') === 'authorize_capture' && $this->transaction->getTxnType(
            ) === TransactionInterface::TYPE_CAPTURE && $emailSent == 0;

        $condition3 = $this->config->getValue('order_email') === 'authorize' && $this->transaction->getTxnType(
            ) === TransactionInterface::TYPE_CAPTURE && $payload->data->metadata->methodId === 'checkoutcom_apm' && $emailSent == 0;

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
    public function processVoid(): void
    {
        $isVoid = $this->transaction->getTxnType() === TransactionInterface::TYPE_VOID;
        if ($isVoid && $this->config->getValue('order_status_voided') === 'canceled') {
            $this->orderManagement->cancel($this->order->getEntityId());
        }
    }

    /**
     * Build a flat array from the gateway response
     *
     * @param mixed[] $data
     *
     * @return array
     */
    public function buildDataArray(array $data): array
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
     * @param float $amount
     *
     * @return string
     */
    public function getFormattedAmount(float $amount): string
    {
        return $this->order->formatPriceTxt($amount);
    }

    /**
     * Check if a refund is partial
     *
     * @param float               $amount
     * @param bool                $isRefund
     * @param OrderInterface|null $order
     * @param bool                $processed
     *
     * @return bool
     */
    public function isPartialRefund(float $amount, bool $isRefund, OrderInterface $order = null, bool $processed = false): bool
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
     * @param float $amount
     * @param bool  $isCapture
     *
     * @return bool
     */
    public function isPartialCapture(float $amount, bool $isCapture): bool
    {
        // Get the total captured
        $totalCaptured = $this->order->getTotalInvoiced();

        // Check the partial capture case
        $isPartialCapture = $this->order->getGrandTotal() > ($totalCaptured + $amount);

        return $isPartialCapture && $isCapture;
    }

    /**
     * Check if payment has been flagged for potential fraud
     *
     * @param mixed $payload
     *
     * @return bool
     */
    public function isFlagged($payload): bool
    {
        return isset($payload->data->risk->flagged) && $payload->data->risk->flagged === true;
    }

    /**
     * Return transaction details for additional logging.
     *
     * @param OrderInterface $order
     *
     * @return mixed[]
     */
    public function getTransactionDetails(OrderInterface $order): array
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
