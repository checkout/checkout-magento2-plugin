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
use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class OrderStatusHandlerService
 */
class OrderStatusHandlerService
{
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $transactionHandler field
     *
     * @var TransactionHandlerService $transactionHandler
     */
    private $transactionHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * $orderManagement field
     *
     * @var OrderManagementInterface $orderManagement
     */
    private $orderManagement;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;
    /**
     * $registry field
     *
     * @var Registry $registry
     */
    private $registry;
    /**
     * $state field
     *
     * @var State $state
     */
    private $state;
    /**
     * $status field
     *
     * @var Status $status
     */
    private $status;
    /**
     * $order field
     *
     * @var Order $order
     */
    private $order;

    /**
     * OrderStatusHandlerService constructor
     *
     * @param StoreManagerInterface     $storeManager
     * @param TransactionHandlerService $transactionHandler
     * @param Config                    $config
     * @param OrderHandlerService       $orderHandler
     * @param OrderManagementInterface  $orderManagement
     * @param OrderRepositoryInterface  $orderRepository
     * @param Registry                  $registry
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        TransactionHandlerService $transactionHandler,
        Config $config,
        OrderHandlerService $orderHandler,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        Registry $registry
    ) {
        $this->storeManager       = $storeManager;
        $this->transactionHandler = $transactionHandler;
        $this->config             = $config;
        $this->orderHandler       = $orderHandler;
        $this->orderManagement    = $orderManagement;
        $this->orderRepository    = $orderRepository;
        $this->registry           = $registry;
    }

    /**
     * Set the current order status
     *
     * @param OrderInterface $order
     * @param mixed[]        $webhook
     *
     * @return void
     * @throws Exception
     */
    public function setOrderStatus(OrderInterface $order, array $webhook): void
    {
        // Initialise state, status & order
        $this->state  = null;
        $this->status = null;
        $this->order  = $order;

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

        if ($this->state) {
            // Set the order state
            $this->order->setState($this->state);
        }

        if ($this->status && $this->order->getStatus() !== 'closed') {
            // Set the order status
            $this->order->setStatus($this->status);
        }

        // Check if order has not been deleted
        if ($this->orderHandler->isOrder($this->order)) {
            // Save the order
            $this->orderRepository->save($this->order);
        }
    }

    /**
     * Sets status/deletes order based on user config if payment fails
     *
     * @param OrderInterface $order
     * @param mixed          $webhook
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function handleFailedPayment(OrderInterface $order, $webhook = false): void
    {
        $failedWebhooks = [
            "payment_declined",
            "payment_expired",
            "payment_cancelled",
            "payment_voided",
            "payment_capture_declined",
        ];

        if (!$webhook || in_array($webhook, $failedWebhooks)) {
            // Get store code
            $storeCode = $this->storeManager->getStore()->getCode();
            // Get config for failed payments
            $config = $this->config->getValue('order_action_failed_payment', null, $storeCode);

            if ($config === 'cancel' || $config === 'delete') {
                if ($order->getState() !== 'canceled') {
                    $this->orderManagement->cancel($order->getId());
                }

                if ($config === 'delete') {
                    $this->registry->register('isSecureArea', true);
                    $this->orderRepository->delete($order);
                    $this->registry->unregister('isSecureArea');
                }
            }
        }
    }

    /**
     * Set the order status for a payment_approved webhook
     *
     * @param mixed [] $webhook
     *
     * @return void
     */
    public function approved(array $webhook): void
    {
        $payload      = json_decode($webhook['event_data']);
        $this->status = $this->config->getValue('order_status_authorized');

        // Flag order if potential fraud
        if ($this->transactionHandler->isFlagged($payload)) {
            $this->status = $this->config->getValue('order_status_flagged');
        }
    }

    /**
     * Set the order status for a payment_captured webhook
     *
     * @return void
     */
    protected function captured(): void
    {
        $this->status = $this->order->getIsVirtual() ? 'complete' : $this->config->getValue('order_status_captured');
        $this->state  = Order::STATE_PROCESSING;
    }

    /**
     * Set the order status for a payment_void webhook
     *
     * @return void
     */
    public function void(): void
    {
        $this->status = $this->config->getValue('order_status_voided');
    }

    /**
     * Set the order status for a refunded webhook
     *
     * @param mixed[] $webhook
     *
     * @return void
     */
    protected function refund(array $webhook): void
    {
        // Format the amount
        $payload = json_decode($webhook['event_data']);
        $amount  = $this->transactionHandler->amountFromGateway(
            $payload->data->amount,
            $this->order
        );

        $isPartialRefund = $this->transactionHandler->isPartialRefund(
            $amount,
            true,
            $this->order,
            true
        );
        $this->status    = $isPartialRefund ? 'order_status_refunded' : 'closed';
        $this->status    = $this->config->getValue($this->status);
        $this->state     = $isPartialRefund ? Order::STATE_PROCESSING : Order::STATE_CLOSED;
    }

    /**
     * Set the order status for a payment_capture_pending webhook
     *
     * @param mixed[] $webhook
     *
     * @return void
     */
    protected function capturePending(array $webhook): void
    {
        $payload = json_decode($webhook['event_data']);
        if (isset($payload->data->metadata->methodId) && $payload->data->metadata->methodId === 'checkoutcom_apm') {
            $this->state  = Order::STATE_PENDING_PAYMENT;
            $this->status = $this->order->getConfig()->getStateDefaultStatus($this->state);
            $this->order->addStatusHistoryComment(__('Payment capture initiated, awaiting capture confirmation.'));
        }
    }

    /**
     * Set the order status for a payment expired webhook.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function paymentExpired(): void
    {
        $this->order->addStatusHistoryComment(__('3DS payment expired.'));
        $this->handleFailedPayment($this->order);
    }
}
