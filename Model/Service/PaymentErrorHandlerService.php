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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;

/**
 * Class PaymentErrorHandlerService
 */
class PaymentErrorHandlerService
{
    /**
     * TRANSACTION_ERROR_LABEL const
     *
     * @var array TRANSACTION_ERROR_LABEL
     */
    const TRANSACTION_ERROR_LABEL = [
        'payment_declined'         => 'Failed payment authorization',
        'payment_capture_declined' => 'Failed payment capture',
        'payment_void_declined'    => 'Failed payment void',
        'payment_refund_declined'  => 'Failed payment refund',
        'payment_pending'          => 'Failed payment request',
    ];
    /**
     * $transactionHandler field
     *
     * @var TransactionHandlerService $transactionHandler
     */
    private $transactionHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
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
     * PaymentErrorHandlerService constructor
     *
     * @param TransactionHandlerService             $transactionHandler
     * @param OrderHandlerService                   $orderHandler
     * @param OrderRepositoryInterface              $orderRepository
     * @param OrderPaymentRepositoryInterface       $orderPaymentRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    public function __construct(
        TransactionHandlerService $transactionHandler,
        OrderHandlerService $orderHandler,
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->transactionHandler           = $transactionHandler;
        $this->orderHandler                 = $orderHandler;
        $this->orderRepository              = $orderRepository;
        $this->orderPaymentRepository       = $orderPaymentRepository;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * Log payment error for webhooks
     *
     * @param mixed          $response
     * @param OrderInterface $order
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function logError($response, OrderInterface $order): void
    {
        // Assign the payment instance
        $payment = $order->getPayment();

        // Prepare the failed transaction info
        $suffix = __(
            ' for an amount of %1. Action ID: %2. Event ID: %3. Payment ID: %4. Error: %5 %6',
            $this->prepareAmount($response->data->amount, $order),
            !empty($response->data->action_id) ? $response->data->action_id : __('Not specified.'),
            !empty($response->id) ? $response->id : __('Not specified.'),
            !empty($response->data->id) ? $response->data->id : __('Not specified.'),
            !empty($response->data->response_code) ? $response->data->response_code : __('Not specified.'),
            !empty($response->data->response_summary) ? $response->data->response_summary : __('Not specified.')
        );

        // Add the order comment
        $previousComment = $this->orderHandler->getStatusHistoryByEntity('3ds Fail', $order);
        if ($previousComment && $response->type === 'payment_declined') {
            $previousComment->setEntityName('order')->setComment(
                __(self::TRANSACTION_ERROR_LABEL[$response->type]) . $suffix
            );
            $this->orderStatusHistoryRepository->save($previousComment);
        } else {
            // Add the order comment
            $order->addStatusHistoryComment(
                __(self::TRANSACTION_ERROR_LABEL[$response->type]) . $suffix
            );
        }

        // Save the data
        $this->orderPaymentRepository->save($payment);
        $this->orderRepository->save($order);
    }

    /**
     * Prepare the amount received from the gateway
     *
     * @param float|int      $amount
     * @param OrderInterface $order
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareAmount($amount, OrderInterface $order): string
    {
        // Prepare the amount
        $amount = $this->transactionHandler->amountFromGateway(
            $amount,
            $order
        );

        // Get the currency
        $currency = $this->orderHandler->getOrderCurrency($order);

        return $amount . ' ' . $currency;
    }

    /**
     * Log the error for 3ds declined payments
     *
     * @param mixed          $response
     * @param OrderInterface $order
     * @param string         $status
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function logPaymentError($response, OrderInterface $order, string $status = ''): void
    {
        // Assign the payment instance
        $payment = $order->getPayment();

        // Prepare the failed transaction info
        $suffix = __(
            ' for an amount of %1. Payment ID: %2',
            $this->prepareAmount($response->amount, $order),
            $response->id
        );

        // Add the order comment
        $order->setHistoryEntityName('3ds Fail');
        $order->addStatusHistoryComment(
            __(self::TRANSACTION_ERROR_LABEL['payment_declined']) . $suffix,
            $status
        );

        // Save the data
        $this->orderPaymentRepository->save($payment);
        $this->orderRepository->save($order);
    }

    /**
     * Description getErrorMessage function
     *
     * @param string $responseCode
     *
     * @return Phrase
     */
    public function getErrorMessage(string $responseCode): Phrase
    {
        $generalErrors = array_fill_keys(
            [
                "20067",
                "20099",
            ],
            __(
                'The payment was declined, please try again. If the problem persists, try another card or payment method.'
            )
        );

        $fundingErrors = array_fill_keys(
            [
                "20001",
                "20002",
                "20005",
                "20010",
                "20032",
                "20039",
                "20040",
                "20044",
                "20046",
                "20051",
                "20052",
                "20053",
                "20061",
                "20062",
                "20065",
                "20075",
                "20083",
                "20084",
                "20085",
                "20091",
                "20093",
                "200N0",
                "200O5",
                "200P1",
                "200P9",
                "200R1",
                "200R3",
                "200S4",
                "200T3",
                "200T5",
                "20103",
                "20108",
                "20150",
                "30004",
                "30021",
                "30022",
                "30035",
                "30036",
                "30038",
            ],
            __(
                'You have reached the limit allowed for this card/account, please try again with another card or payment method.'
            )
        );

        $technicalErrors = array_fill_keys(
            [
                "20003",
                "20060",
                "20102",
                "20112",
                "20121",
                "30016",
                "30017",
                "30018",
                "30019",
                "20006",
                "20009",
                "20019",
                "20020",
                "20021",
                "20022",
                "20023",
                "20024",
                "20025",
                "20026",
                "20027",
                "20028",
                "20029",
                "20030",
                "20031",
                "20042",
                "20058",
                "20064",
                "20068",
                "20086",
                "20088",
                "20089",
                "20090",
                "20092",
                "20094",
                "20095",
                "20096",
                "20097",
                "20098",
                "200T2",
                "20101",
                "20104",
                "20105",
                "20106",
                "20107",
                "20109",
                "20110",
                "20111",
                "20113",
                "20114",
                "20115",
                "20116",
                "20117",
                "20118",
                "20119",
                "20120",
                "20123",
                "30015",
                "30020",
                "20059",
                "20063",
                "20066",
                "20082",
                "30007",
                "30034",
                "30037",
                "4XXXX" // Fraud response codes
            ],
            __('Something went wrong, please try again later')
        );

        $invalidCardErrors = array_fill_keys(
            [
                "20014",
                "20054",
                "20055",
                "20056",
                "20087",
                "200N7",
                "20100",
                "30033",
                "30041",
                "30043",
            ],
            __('It looks like your card is invalid or blocked, please try with another card')
        );

        $blockedCardErrors = array_fill_keys(
            [
                "20017",
                "20018",
                "20057",
            ],
            __(
                'It looks like this transaction has been blocked due to account holder action, please contact your bank or use another card or payment method'
            )
        );

        $threeDsErrors = array_fill_keys(
            [
                "20151",
                "20152",
                "20154",
            ],
            __('3DS has expired or authentication failed, please try again')
        );

        $messageMapper = $generalErrors + $fundingErrors + $technicalErrors + $invalidCardErrors + $blockedCardErrors + $threeDsErrors;

        return $messageMapper[$responseCode] ?? __('The transaction could not be processed');
    }
}
