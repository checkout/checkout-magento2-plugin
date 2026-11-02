<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Payment;

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\FlowPaymentAttemptService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Provider\OrderSettings;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class FailFlowOrder extends AbstractPayment
{
    /**
     * Payment statuses for which the order is canceled and the cart restored
     */
    private const FAILED_PAYMENT_STATUSES = ['Declined', 'Canceled', 'Expired'];

    /**
     * Payment statuses for which the order is left untouched and the shopper told the payment went through
     */
    private const SUCCESSFUL_PAYMENT_STATUSES = ['Authorized', 'Captured', 'Partially Captured', 'Paid'];

    /**
     * Payment statuses Checkout.com can still turn into a successful payment: the order and the cart are left untouched
     */
    private const PENDING_PAYMENT_STATUSES = ['Pending', 'Retry Scheduled'];

    private OrderSettings $orderSettings;
    private OrderStatusHandlerService $orderStatusHandler;
    private PaymentErrorHandlerService $paymentErrorHandlerService;
    private FlowPaymentAttemptService $paymentAttemptService;
    private CartRepositoryInterface $cartRepository;

    public function __construct(
        ApiHandlerService $apiHandler,
        Context $context,
        Logger $logger,
        ManagerInterface $messageManager,
        OrderHandlerService $orderHandler,
        OrderSettings $orderSettings,
        OrderStatusHandlerService $orderStatusHandler,
        PaymentErrorHandlerService $paymentErrorHandlerService,
        Session $session,
        StoreManagerInterface $storeManager,
        FlowPaymentAttemptService $paymentAttemptService,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct(
            $apiHandler,
            $context,
            $logger,
            $messageManager,
            $orderHandler,
            $session,
            $storeManager,
        );

        $this->orderSettings = $orderSettings;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->paymentErrorHandlerService = $paymentErrorHandlerService;
        $this->paymentAttemptService = $paymentAttemptService;
        $this->cartRepository = $cartRepository;
    }

    protected function paymentAction(array $apiCallResponse, OrderInterface $order): ResponseInterface
    {
        $response = $apiCallResponse['response'];

        $this->logger->display($response);

        // This URL can be called by anyone with any reference: only handle the order placed in
        // the current checkout session, never another customer's order
        if ($order->getIncrementId() !== $this->session->getLastRealOrderId()) {
            $this->logger->write(
                sprintf(
                    'Failed payment %s ignored: order %s does not belong to the current checkout session',
                    $response['id'] ?? '',
                    $order->getIncrementId()
                )
            );

            $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        // The failed payment belongs to another attempt sharing the same reference:
        // never cancel or delete the order of the current attempt
        if ($this->paymentAttemptService->isFromAnotherAttempt($order, $response)) {
            $this->logger->write(
                sprintf(
                    'Failed payment %s ignored: it belongs to another attempt than order %s',
                    $response['id'] ?? '',
                    $order->getIncrementId()
                )
            );

            $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        $paymentStatus = $this->getPaymentStatus($response);

        // The URL can be replayed after a successful payment (open tab, history):
        // never cancel the order of an authorized or captured payment
        if (in_array($paymentStatus, self::SUCCESSFUL_PAYMENT_STATUSES, true)) {
            $this->logger->write(
                sprintf(
                    'Failed payment %s ignored: payment of order %s has status "%s"',
                    $response['id'] ?? '',
                    $order->getIncrementId(),
                    $paymentStatus
                )
            );

            $this->messageManager->addNoticeMessage(
                __('The payment of order %1 has already been accepted.', $order->getIncrementId())
            );

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        // A pending payment may still succeed (APM approved late in the banking app) and a missing
        // status cannot be trusted: leave the order and the cart untouched until Checkout.com settles
        // the payment, so the webhooks or the pending payment cron cancel or confirm the order and
        // the shopper cannot pay twice
        if ($paymentStatus === '' || in_array($paymentStatus, self::PENDING_PAYMENT_STATUSES, true)) {
            $this->logger->write(
                sprintf(
                    'Failed payment %s: order %s left to the webhooks, payment status is "%s"',
                    $response['id'] ?? '',
                    $order->getIncrementId(),
                    $paymentStatus
                )
            );

            $this->messageManager->addNoticeMessage(
                __(
                    'The payment for order %1 is still pending. Please wait a few minutes before placing a new order.',
                    $order->getIncrementId()
                )
            );

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        // Safety net: only a confirmed failure cancels the order. Any other status (Voided, Refunded...)
        // is left to the webhooks
        if (!in_array($paymentStatus, self::FAILED_PAYMENT_STATUSES, true)) {
            $this->logger->write(
                sprintf(
                    'Failed payment %s ignored: unexpected payment status "%s" for order %s',
                    $response['id'] ?? '',
                    $paymentStatus,
                    $order->getIncrementId()
                )
            );

            $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $action = $this->orderSettings->getActionOnFailedPayment($websiteCode);
        $status = $action === 'cancel' ? 'canceled' : 'false';

        $this->paymentErrorHandlerService->logPaymentError(
            $response,
            $order,
            $status
        );

        $this->session->restoreQuote();
        $this->resetReservedOrderId();
        $this->orderStatusHandler->handleFailedPayment($order);
        $this->orderHandler->deleteOrder($order);

        $errorMessage = null;
        $type = $response['source']['type'] ?? '';

        if (isset($response['actions'][0]['response_code']) && $type !== 'knet') {
            $errorMessage = $this->paymentErrorHandlerService->getErrorMessage(
                $response['actions'][0]['response_code']
            );
        }

        $this->messageManager->addErrorMessage(
            $errorMessage ? $errorMessage->getText() : __('The transaction could not be processed.')
        );

        if (isset($response['metadata']['failureUrl'])) {
            return $this->_redirect($response['metadata']['failureUrl']);
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }

    /**
     * Status of the payment, fetched from its details when the reference search result does not carry it
     */
    private function getPaymentStatus(array $response): string
    {
        if (isset($response['status']) && is_string($response['status'])) {
            return $response['status'];
        }

        if (empty($response['id'])) {
            return '';
        }

        try {
            $storeCode = $this->storeManager->getStore()->getCode();
            $details = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE)
                ->getPaymentDetails((string)$response['id']);
        } catch (Exception $e) {
            $this->logger->write(
                sprintf('Unable to get the status of payment %s: %s', $response['id'], $e->getMessage())
            );

            return '';
        }

        return is_string($details['status'] ?? null) ? $details['status'] : '';
    }

    /**
     * Force a new increment id on the next attempt so it never shares the reference of the failed one
     */
    private function resetReservedOrderId(): void
    {
        $quote = $this->session->getQuote();

        if (!$quote->getId() || !$quote->getReservedOrderId()) {
            return;
        }

        $quote->setReservedOrderId(null);
        $this->cartRepository->save($quote);
    }

    protected function saveCardAction(array $apiCallResponse): ResponseInterface
    {
        $this->messageManager->addErrorMessage(
            __('The card could not be saved.')
        );

        return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
    }
}
