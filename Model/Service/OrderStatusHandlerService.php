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
 * Class OrderStatusHandlerService.
 */
class OrderStatusHandlerService
{
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * @var State
     */
    public $state;

    /**
     * @var Status
     */
    public $status;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var Payload
     */
    public $payload;
    
    /**
     * OrderStatusHandlerService constructor.
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \Magento\Sales\Model\Order $orderModel,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler
    ) {
        $this->storeManager          = $storeManager;
        $this->transactionHandler    = $transactionHandler;
        $this->orderModel            = $orderModel;
        $this->config                = $config;
        $this->orderHandler          = $orderHandler;
        
    }
    
    /**
     * Set the current order status.
     */
    public function setOrderStatus($order, $webhook)
    {
        // Initialise state, status & order
        $this->state = null;
        $this->status = null;
        $this->order = $order;
        
        switch ($webhook['event_type']) {
            case 'payment_approved':
                $this->approved($webhook);
                break;
            case 'payment_captured':
                $this->captured();
                break;
            case 'payment_voided':
                $this->void();
                break;
            case 'payment_refunded':
                $this->refund($webhook);
                break;
            case 'payment_capture_pending':
                $this->capturePending($webhook);
                break;
            case 'payment_expired':
                $this->paymentExpired();
                break;
        }
        
        if ($this->state && $this->order->getStatus() != 'holded') {
            // Set the order state
            $this->order->setState($this->state);
        }
        
        if ($this->status && $this->order->getStatus() != 'closed' && $this->order->getStatus() != 'holded') {
            // Set the order status
            $this->order->setStatus($this->status);
        }

        // Check if order has not been deleted
        if ($this->orderHandler->isOrder($this->order)) {
            // Save the order
            $this->order->save();
        }
    }

    /**
     * Sets status/deletes order based on user config if payment fails
     */
    public function handleFailedPayment($order, $webhook = false)
    {
        $failedWebhooks = [
            "payment_declined",
            "payment_expired",
            "payment_cancelled",
            "payment_voided",
            "payment_capture_declined"
        ];

        if (!$webhook || in_array($webhook, $failedWebhooks)) {
            // Get store code
            $storeCode = $this->storeManager->getStore()->getCode();
            // Get config for failed payments
            $config = $this->config->getValue('order_action_failed_payment', null, $storeCode);

            if ($config == 'cancel' || $config == 'delete') {
                if ($order->getState() !== 'canceled') {
                    $this->orderModel->loadByIncrementId($order->getIncrementId())->cancel();
                    $order->setStatus($this->config->getValue('order_status_canceled'));
                    $order->setState($this->orderModel::STATE_CANCELED);
                    $order->save();
                }

                if ($config == 'delete') {
                    $this->registry->register('isSecureArea', true);
                    $this->orderRepository->delete($order);
                    $this->registry->unregister('isSecureArea');
                }
            }
        }
    }

    /**
     * Set the order status for a payment_approved webhook.
     */
    public function approved($webhook) {
        $payload = json_decode($webhook['event_data']);
        if ($this->order->getState() !== 'processing') {
            $this->status = $this->config->getValue('order_status_authorized');
        }
        // Flag order if potential fraud
        if ($this->transactionHandler->isFlagged($payload)) {
            $this->status = $this->config->getValue('order_status_flagged');
        }
    }

    /**
     * Set the order status for a payment_captured webhook.
     */
    public function captured() {
        $this->status = $this->config->getValue('order_status_captured');
        $this->state = $this->orderModel::STATE_PROCESSING;
    }

    /**
     * Set the order status for a payment_void webhook.
     */
    public function void() {
        $this->status = $this->config->getValue('order_status_voided');
        $this->state = $this->orderModel::STATE_CANCELED;
    }

    /**
     * Set the order status for a refunded webhook.
     */
    public function refund($webhook) {
        // Format the amount
        $payload = json_decode($webhook['event_data']);
        $amount = $this->transactionHandler->amountFromGateway(
            $payload->data->amount,
            $this->order
        );
        
        $isPartialRefund = $this->transactionHandler->isPartialRefund(
            $amount,
            true,
            $this->order
        );
        $this->status = $isPartialRefund ? 'order_status_captured' : 'order_status_refunded';
        $this->status = $this->config->getValue($this->status);
        $this->state = $isPartialRefund ? $this->orderModel::STATE_PROCESSING : $this->orderModel::STATE_CLOSED;
    }

    /**
     * Set the order status for a payment_capture_pending webhook.
     */
    public function capturePending($webhook) {
        $payload = json_decode($webhook['event_data']);
        if (isset($payload->data->metadata->methodId)
            && $payload->data->metadata->methodId === 'checkoutcom_apm'
        ) {
            $this->state = $this->orderModel::STATE_PENDING_PAYMENT;
            $this->status = $this->order->getConfig()->getStateDefaultStatus($this->state);
            $this->order->addStatusHistoryComment(__('Payment capture initiated, awaiting capture confirmation.'));
        }
    }

    /**
     * Set the order status for a payment expired webhook.
     */
    public function paymentExpired()
    {
        $this->order->addStatusHistoryComment(__('3DS payment expired.'));
        $this->handleFailedPayment($this->order);
    }
}
