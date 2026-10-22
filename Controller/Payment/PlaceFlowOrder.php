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
use CheckoutCom\Magento2\Model\Service\ApiOrderRollback;
use CheckoutCom\Magento2\Model\Service\ApiPaymentMethodGuard;
use CheckoutCom\Magento2\Model\Service\FlowPaymentAttemptService;
use CheckoutCom\Magento2\Model\Service\FlowSessionCurrencyGuard;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class PlaceFlowOrder extends Action
{
    private const METHOD_PREFIX = "checkoutcom_";
    private const FLOW_ID = "checkoutcom_flow";

    /**
     * Flow payment type sent by the storefront (card, applepay, googlepay, paypal, tamara...)
     */
    private const SELECTED_METHOD_PATTERN = '/^[a-z0-9_]{1,50}$/';

    public function __construct(
        Context $context,
        private readonly CheckoutSession $checkoutSession,
        private readonly FlowGeneralSettings $flowGeneralConfig,
        private readonly FlowSessionCurrencyGuard $currencyGuard,
        private readonly JsonFactory $jsonFactory,
        private readonly Logger $logger,
        private readonly OrderHandlerService $orderHandler,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly QuoteHandlerService $quoteHandler,
        private readonly StoreManagerInterface $storeManager,
        private readonly ApiPaymentMethodGuard $apiPaymentMethodGuard,
        private readonly ApiOrderRollback $apiOrderRollback,
        private readonly LoggerInterface $errorLogger
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        return $this->processChecks();
    }

    private function processChecks(): Json
    {
        $quote = null;
        $order = null;

        try {
            $json =  $this->jsonFactory->create();
            $websiteCode = $this->storeManager->getWebsite()->getCode();


            if (!$this->flowGeneralConfig->useFlow($websiteCode)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('Configuration Error'),
                ]);
            }

            $quote = $this->quoteHandler->getQuote();
            $data = $this->getRequest()->getParams();

            if (!isset($data['selectedMethod'])
                || !is_string($data['selectedMethod'])
                || !preg_match(self::SELECTED_METHOD_PATTERN, $data['selectedMethod'])
            ) {
                return $json->setData([
                    'success' => false,
                    'message' => __('Please enter valid payment details'),
                ]);
            }

            if (!$this->getRequest()->isAjax()) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The request is invalid'),
                ]);
            }

            if (empty($quote)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('No quote found'),
                ]);
            }

            // Checkout.com fixes the Flow session currency at creation. If the shopper switched
            // store currency (possibly from another tab) after the session was prepared, refuse
            // here so we never create an order bound to a session in the wrong currency. Placing
            // the order first and only catching this at submit time would leave an orphaned
            // pending-payment order behind.
            $sessionId = isset($data['session_id']) ? (string)$data['session_id'] : null;

            if ($this->currencyGuard->hasCurrencyChanged($sessionId, (string)$quote->getQuoteCurrencyCode())) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The currency has changed since payment was initialized. Please refresh the page and try again.'),
                ]);
            }

            // Only an available Flow method can place an order
            if (!$this->apiPaymentMethodGuard->isAllowed(self::FLOW_ID, $quote)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The payment method is not available.'),
                ]);
            }

            $order = $this->orderHandler->setMethodId(self::FLOW_ID)->handleOrder($quote);

            if (!$this->orderHandler->isOrder($order)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The order could not be processed.'),
                ]);
            }

            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $this->attachPaymentInfos($order, $data['selectedMethod'], $sessionId);
            $this->orderRepository->save($order);

            return $json->setData([
                'success' => true,
                'reference' => $order->getIncrementId()
            ]);

        } catch (Throwable $exception) {
            $this->logger->write($exception->getMessage());
            $this->handleProcessingError($exception, $order, $quote);

            return $this->jsonFactory->create()->setData([
                'success' => false,
                'message' => __('An error has occurred, please select another payment method'),
            ]);
        }
    }

    /**
     * Roll back the order after an unexpected error so that no order is left without payment.
     * The payment is made afterwards by the Flow component, so the order cannot have been paid yet.
     *
     * @param Throwable $exception
     * @param mixed $order
     * @param mixed $quote
     *
     * @return void
     */
    private function handleProcessingError(Throwable $exception, $order, $quote): void
    {
        // The order may have been placed even if the error occurred while creating it
        if (!$this->orderHandler->isOrder($order)) {
            $order = $this->apiOrderRollback->findQuoteOrder($quote ?: null);
        }

        if (!$this->orderHandler->isOrder($order)) {
            return;
        }

        $this->errorLogger->error('Checkout.com Flow place order failed', ['exception' => $exception]);

        try {
            // Restore the quote first: it relies on the last order of the session
            $this->checkoutSession->restoreQuote();

            if (!$this->apiOrderRollback->rollbackOnError($order)) {
                $this->errorLogger->critical('Checkout.com could not cancel order ' . $order->getIncrementId());
            }
        } catch (Throwable $rollbackException) {
            $this->errorLogger->critical(
                'Checkout.com could not roll back order ' . $order->getIncrementId(),
                ['exception' => $rollbackException]
            );
        }
    }

    private function attachPaymentInfos($order, $paymentName, ?string $sessionId) {
        $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();

        $methodId = self::METHOD_PREFIX . $paymentName;

        $paymentInfo->setAdditionalInformation(
            'flow_method_id',
            $methodId
        );

        // Bind the order to this payment attempt so webhooks of another attempt sharing the same
        // reference can be ignored instead of cancelling or deleting this order
        $attemptId = $this->getSessionAttemptId($sessionId);
        if ($attemptId) {
            $paymentInfo->setAdditionalInformation(FlowPaymentAttemptService::ATTEMPT_ID_KEY, $attemptId);
        }

        $order->setPayment($paymentInfo);
    }

    /**
     * Attempt token recorded by FlowPrepareService for the given Flow session, or null when unknown.
     */
    private function getSessionAttemptId(?string $sessionId): ?string
    {
        if (!$sessionId) {
            return null;
        }

        $sessions = $this->checkoutSession->getFlowSessionAttemptIds() ?? [];

        return $sessions[$sessionId] ?? null;
    }
}
