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

/**
 * Class PaymentErrorHandlerService.
 */
class PaymentErrorHandlerService
{
    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var array
     */
    public static $transactionErrorLabel = [
        'payment_declined' => 'Failed payment authorization',
        'payment_capture_declined' => 'Failed payment capture',
        'payment_void_declined' => 'Failed payment void',
        'payment_refund_declined' => 'Failed payment refund'
    ];

    /**
     * PaymentErrorHandlerService constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler
    ) {
        $this->webhookHandler = $webhookHandler;
        $this->orderHandler = $orderHandler;
    }
    
    /**
     * Create a transaction for an order.
     */
    public function logError($response, $order)
    {
        // Assign the order instance
        $this->order = $order;

        // Assign the payment instance
        $this->payment = $this->order->getPayment();

        // Prepare the failed transaction info
        $suffix = __(
            ' for an amount of %1. Action ID: %2. Event ID: %3. Payment ID: %4. Error: %5 %6',
            $this->prepareAmount($response->data->amount),
            $response->data->action_id,
            $response->id,
            $response->data->id,
            $response->data->response_code,
            !empty($response->data->response_summary)
            ? $response->data->response_summary
            : __('Not specified.')
        );

        // Add the order comment
        $this->order->addStatusHistoryComment(
            __(self::$transactionErrorLabel[$response->type]) . $suffix
        );

        // Save the data
        $this->payment->save();
        $this->order->save();
    }

    /**
     * Prepare the amount received from the gateway.
     */
    public function prepareAmount($amount)
    {
        // Prepare the amount
        $amount = $this->webhookHandler->amountFromGateway(
            $amount,
            $this->order
        );
        
        // Get the currency
        $currency =  $this->orderHandler->getOrderCurrency($this->order);

        return $amount . ' ' . $currency;
    }
}
