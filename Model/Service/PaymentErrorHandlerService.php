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
        switch ($responseCode) {
            case '20001':
            case '20002':
            case '20005':
            case '20010':
            case '20032':
            case '20039':
            case '20040':
            case '20044':
            case '20046':
            case '20051':
            case '20052':
            case '20053':
            case '20061':
            case '20062':
            case '20065':
            case '20075':
            case '20083':
            case '20084':
            case '20085':
            case '20091':
            case '20093':
            case '200N0':
            case '200O5':
            case '200P1':
            case '200P9':
            case '200R1':
            case '200R3':
            case '200S4':
            case '200T3':
            case '200T5':
            case '20103':
            case '20108':
            case '20150':
            case '30004':
            case '30021':
            case '30022':
            case '30035':
            case '30036':
            case '30038':
                return __('You have reached the limit allowed for this card/account, please try again with another card or payment method.');
            case '20006':
                return __('Invalid payment request. Please check your details and try again.');
            case '20019':
                return __('Your session expired. Please start your payment again.');
            case '20023':
                return __('This transaction cannot be completed with this card. Please use another payment method.');
            case '20031':
                return __('This bank or card is not supported. Please use another payment method.');
            case '20058':
                return __('This transaction is not permitted with this card. Please use another payment method.');
            case '20059':
                return __('Payment was blocked for security reasons. Please use another card.');
            case '20066':
            case '20063':
                return __('Payment was blocked for security reasons. Please contact your bank.');
            case '20067':
            case '20099':
                return __('The payment was declined, please try again. If the problem persists, try another card or payment method.');
            case '20068':
                return __('The request timed out. Please try again.');
            case '20082':
                return __('Security check failed. Please verify your details and try again.');
            case '20101':
                return __('We couldn\'t find this account. Please check your card details.');
            case '20102':
                return __('A configuration issue occurred. Please contact support.');
            case '20104':
                return __('Your card could not be processed. Please use another card.');
            case '20105':
                return __('Payment could not be processed. Please try again later.');
            case '20106':
                return __('This currency is not supported. Please use another payment method.');
            case '20110':
                return __('This payment has already been authorised.');
            case '20112':
                return __('This card cannot be authenticated. Please use another card.');
            case '20114':
                return __('Your session expired. Please refresh the page and try again.');
            case '20120':
                return __('Please complete all required fields correctly.');
            case '20121':
                return __('This transaction exceeds allowed limits. Please use another card.');
            case '4XXXX':
                return __('Additional verification is required to complete your payment.');
            case '20003':
            case '20020':
            case '20021':
            case '20022':
            case '20024':
            case '20028':
            case '20029':
            case '20086':
            case '20088':
            case '20089':
            case '20090':
            case '20092':
            case '20095':
            case '20096':
            case '20097':
            case '20098':
                return __('We\'re unable to process your payment right now. Please try again.');
            case '20009':
            case '20118':
                return __('Your payment is being processed. Please wait.');
            case '20025':
            case '20027':
            case '20030':
                return __('Some payment details are incorrect. Please check and try again.');
            case '20026':
            case '20094':
                return __('This payment has already been processed.');
            case '20042':
            case '20064':
            case '200T2':
                return __('The payment information provided is invalid. Please check and try again.');
            case '20107':
            case '20123':
                return __('Please complete all required fields before proceeding.');
            case '20109':
            case '20111':
                return __('This transaction was already reversed.');
            case '20113':
            case '20115':
            case '20116':
                return __('Invalid payment request. Please try again.');
            case '20117':
            case '20119':
                return __('System configuration error. Please contact support.');
            case '20060':
            case '30007':
            case '30015':
            case '30016':
            case '30017':
            case '30020':
            case '30034':
            case '30037':
                return __('Your card was declined. Please try another card.');
            case '30018':
            case '30019':
                return __('Your bank declined the payment. Please contact your bank.');
            case '20014':
            case '20054':
            case '20055':
            case '20056':
            case '20087':
            case '200N7':
            case '20100':
            case '30033':
            case '30041':
            case '30043':
                return __('It looks like your card is invalid or blocked, please try with another card');
            case '20017':
            case '20018':
            case '20057':
                return __('It looks like this transaction has been blocked due to account holder action, please contact your bank or use another card or payment method');
            case '20151':
            case '20152':
            case '20154':
                return __('3DS has expired or authentication failed, please try again');
            default:
                return __('The transaction could not be processed');
        }
    }
}
