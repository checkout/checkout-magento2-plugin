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

namespace CheckoutCom\Magento2\Plugin\Api;

use Checkout\CheckoutArgumentException;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\RefundAdapterInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class RefundInvoice
 */
class RefundInvoice
{
    private MethodHandlerService $methodHandler;
    private StoreManagerInterface $storeManager;
    private ApiHandlerService $apiHandler;
    private Config $config;
    private OrderPaymentRepositoryInterface $orderPaymentRepository;

    public function __construct(
        MethodHandlerService $methodHandler,
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        Config $config,
        OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {
        $this->methodHandler = $methodHandler;
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->config = $config;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    /**
     * Refund the order online
     *
     * @param RefundAdapterInterface $subject
     * @param CreditmemoInterface $creditMemo
     * @param OrderInterface $order
     * @param bool $isOnline
     *
     * @return mixed[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function beforeRefund(
        RefundAdapterInterface $subject,
        CreditmemoInterface $creditMemo,
        OrderInterface $order,
        bool $isOnline = false
    ): array {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        try {
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
        } catch (CheckoutArgumentException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        // Get the method and method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Check if payment method is checkout.com
        if ($isOnline && in_array($methodId, $this->config->getMethodsList())) {
            // Get the payment and amount to refund
            $payment = $order->getPayment();
            $amount = $creditMemo->getBaseGrandTotal();
            $method = $this->methodHandler->get($methodId);

            if (!$method->canRefund()) {
                throw new LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund
            $response = $api->refundOrder($payment, $amount);

            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The refund request could not be processed.')
                );
            }

            if ($this->statusNeedsCorrection($order)) {
                $order->setStatus($this->config->getValue('order_status_refunded'));
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
            $this->orderPaymentRepository->save($payment);

            $result = [$creditMemo, $order, $isOnline];
        } else {
            $result = [$creditMemo, $order, $isOnline];
        }

        return $result;
    }

    /**
     * Description statusNeedsCorrection function
     *
     * @param OrderInterface $order
     *
     * @return bool
     */
    public function statusNeedsCorrection(OrderInterface $order): bool
    {
        $currentState = $order->getState();
        $currentStatus = $order->getStatus();
        $desiredStatus = $this->config->getValue('order_status_refunded');

        return $currentState === Order::STATE_PROCESSING && $currentStatus !== $desiredStatus && $currentStatus !== 'closed';
    }
}
