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
 * @copyright 2010-present Checkout.com all rights reserved
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
     */
    const TRANSACTION_ERROR_LABEL = [
        'payment_declined' => 'Failed payment authorization',
        'payment_capture_declined' => 'Failed payment capture',
        'payment_void_declined' => 'Failed payment void',
        'payment_refund_declined' => 'Failed payment refund',
        'payment_pending' => 'Failed payment request',
    ];

    /**
     * TRANSACTION_SUCCESS_DIGITS const
     */
    const TRANSACTION_SUCCESS_DIGITS = '10';

    private TransactionHandlerService $transactionHandler;
    private OrderHandlerService $orderHandler;
    private OrderRepositoryInterface $orderRepository;
    private OrderPaymentRepositoryInterface $orderPaymentRepository;
    private OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository;

    public function __construct(
        TransactionHandlerService $transactionHandler,
        OrderHandlerService $orderHandler,
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->transactionHandler = $transactionHandler;
        $this->orderHandler = $orderHandler;
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * Log payment error for webhooks
     *
     * @param array $response
     * @param OrderInterface $order
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function logError(array $response, OrderInterface $order): void
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
     * @param float|int $amount
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
     * @param array $response
     * @param OrderInterface $order
     * @param string $status
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function logPaymentError(array $response, OrderInterface $order, string $status = ''): void
    {
        // Assign the payment instance
        $payment = $order->getPayment();

        // Prepare the failed transaction info
        $suffix = __(
            ' for an amount of %1. Payment ID: %2',
            $this->prepareAmount($response['amount'], $order),
            $response['id']
        );

        // Add the order comment
        $order->setHistoryEntityName('3ds Fail');
        $order->addStatusHistoryComment(
            __(self::TRANSACTION_ERROR_LABEL['payment_declined']) . $suffix,
            $status
        );

        // Save the data
        if ($payment) {
            $this->orderPaymentRepository->save($payment);
        }

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
                '20001',
                '20002',
                '20005',
                '20010',
                '20032',
                '20039',
                '20040',
                '20044',
                '20046',
                '20051',
                '20052',
                '20053',
                '20061',
                '20062',
                '20065',
                '20075',
                '20083',
                '20084',
                '20085',
                '20091',
                '20093',
                '200N0',
                '200O5',
                '200P1',
                '200P9',
                '200R1',
                '200R3',
                '200S4',
                '200T3',
                '200T5',
                '20103',
                '20108',
                '20150',
                '30004',
                '30021',
                '30022',
                '30035',
                '30036',
                '30038',
            ],
            __(
                'You have reached the limit allowed for this card/account, please try again with another card or payment method.'
            )
        );

        $technicalErrors = [
            '20006' => __('Invalid payment request. Please check your details and try again.'),
            '20019' => __('Your session expired. Please start your payment again.'),
            '20023' => __('This transaction cannot be completed with this card. Please use another payment method.'),
            '20031' => __('This bank or card is not supported. Please use another payment method.'),
            '20058' => __('This transaction is not permitted with this card. Please use another payment method.'),
            '20059' => __('Payment was blocked for security reasons. Please use another card.'),
            '20060' => __('Your card was declined. Please try another card.'),
            '20063' => __('Payment was blocked for security reasons. Please contact your bank.'),
            '20066' => __('Payment was blocked for security reasons. Please contact your bank.'),
            '20068' => __('The request timed out. Please try again.'),
            '20082' => __('Security check failed. Please verify your details and try again.'),
            '20101' => __('We couldn\'t find this account. Please check your card details.'),
            '20102' => __('A configuration issue occurred. Please contact support.'),
            '20104' => __('Your card could not be processed. Please use another card.'),
            '20105' => __('Payment could not be processed. Please try again later.'),
            '20106' => __('This currency is not supported. Please use another payment method.'),
            '20110' => __('This payment has already been authorised.'),
            '20112' => __('This card cannot be authenticated. Please use another card.'),
            '20114' => __('Your session expired. Please refresh the page and try again.'),
            '20120' => __('Please complete all required fields correctly.'),
            '20121' => __('This transaction exceeds allowed limits. Please use another card.'),
            '4XXXX' => __('Additional verification is required to complete your payment.')
        ];

        $technicalErrorRetry = array_fill_keys(
            [
                '20003',
                '20020',
                '20021',
                '20022',
                '20024',
                '20028',
                '20029',
                '20086',
                '20088',
                '20089',
                '20090',
                '20092',
                '20095',
                '20096',
                '20097',
                '20098'
            ],
            __('We\'re unable to process your payment right now. Please try again.')
        );

        $paymentProcessing = array_fill_keys(
            [
                '20009',
                '20118'
            ],
            __('Your payment is being processed. Please wait.')
        );

        $invalidPaymentDetails = array_fill_keys(
            [
                '20025',
                '20027',
                '20030'
            ],
            __('Some payment details are incorrect. Please check and try again.')
        );

        $duplicateTransaction = array_fill_keys(
            [
                '20026',
                '20094'
            ],
            __('This payment has already been processed.')
        );

        $invalidPaymentDetailsInformation = array_fill_keys(
            [
                '20042',
                '20064',
                '200T2'
            ],
            __('The payment information provided is invalid. Please check and try again.')
        );

        $missingRequiredFields = array_fill_keys(
            [
                '20107',
                '20123'
            ],
            __('Please complete all required fields before proceeding.')
        );

        $transactionAlreadyReversed = array_fill_keys(
            [
                '20109',
                '20111'
            ],
            __('This transaction was already reversed.')
        );

        $invalidRequest = array_fill_keys(
            [
                '20113',
                '20115',
                '20116'
            ],
            __('Invalid payment request. Please try again.')
        );

        $configurationError = array_fill_keys(
            [
                '20117',
                '20119'
            ],
            __('System configuration error. Please contact support.')
        );

        $cardDeclined = array_fill_keys(
            [
                '30007',
                '30015',
                '30016',
                '30017',
                '30020',
                '30034',
                '30037'
            ],
            __('Your card was declined. Please try another card.')
        );

        $cardDeclinedContactBank = array_fill_keys(
            [
                '30018',
                '30019'
            ],
            __('Your bank declined the payment. Please contact your bank.')
        );

        $invalidCardErrors = array_fill_keys(
            [
                '20014',
                '20054',
                '20055',
                '20056',
                '20087',
                '200N7',
                '20100',
                '30033',
                '30041',
                '30043',
            ],
            __('It looks like your card is invalid or blocked, please try with another card')
        );

        $blockedCardErrors = array_fill_keys(
            [
                '20017',
                '20018',
                '20057',
            ],
            __(
                'It looks like this transaction has been blocked due to account holder action, please contact your bank or use another card or payment method'
            )
        );

        $threeDsErrors = array_fill_keys(
            [
                '20151',
                '20152',
                '20154',
            ],
            __('3DS has expired or authentication failed, please try again')
        );

        $messageMapper = $generalErrors + $fundingErrors + $technicalErrors + $technicalErrorRetry + $paymentProcessing + $invalidPaymentDetails + $duplicateTransaction + $invalidPaymentDetailsInformation + $missingRequiredFields + $transactionAlreadyReversed + $invalidRequest + $configurationError + $cardDeclined + $cardDeclinedContactBank + $invalidCardErrors + $blockedCardErrors + $threeDsErrors;

        return $messageMapper[$responseCode] ?? __('The transaction could not be processed');
    }
}
