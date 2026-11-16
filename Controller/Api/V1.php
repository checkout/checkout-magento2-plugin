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

namespace CheckoutCom\Magento2\Controller\Api;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\ApiOrderRollback;
use CheckoutCom\Magento2\Model\Service\ApiPaymentMethodGuard;
use CheckoutCom\Magento2\Model\Service\ApiQuoteResolver;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class V1
 */
class V1 extends Action
{
    private const PAYMENT_METHOD = 'checkoutcom_card_payment';

    private $data;
    private ?CartInterface $quote = null;
    private bool $isPaymentAccepted = false;
    private JsonFactory $jsonFactory;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private QuoteHandlerService $quoteHandler;
    private OrderHandlerService $orderHandler;
    private MethodHandlerService $methodHandler;
    private ApiHandlerService $apiHandler;
    private Utilities $utilities;
    private OrderRepositoryInterface $orderRepository;
    private ApiQuoteResolver $apiQuoteResolver;
    private ApiPaymentMethodGuard $apiPaymentMethodGuard;
    private ApiOrderRollback $apiOrderRollback;
    private CustomerSession $customerSession;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        OrderRepositoryInterface $orderRepository,
        ApiQuoteResolver $apiQuoteResolver,
        ApiPaymentMethodGuard $apiPaymentMethodGuard,
        ApiOrderRollback $apiOrderRollback,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->orderRepository = $orderRepository;
        $this->apiQuoteResolver = $apiQuoteResolver;
        $this->apiPaymentMethodGuard = $apiPaymentMethodGuard;
        $this->apiOrderRollback = $apiOrderRollback;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Handles the controller method.
     * Any error raised once the order exists rolls the order back, so no order is left without payment.
     *
     * @return Json
     */
    public function execute(): Json
    {
        // Set the response parameters
        $success = false;
        $orderId = 0;
        $errorMessage = '';
        $order = null;

        try {
            // Get the request parameters
            $this->data = json_decode($this->getRequest()->getContent());

            // Validate the request
            if ($this->isValidRequest()) {
                // Load the quote and create the order
                $this->quote = $this->loadQuote();
                $order = $this->orderHandler->setMethodId(self::PAYMENT_METHOD)->handleOrder($this->quote);

                // Process the payment
                if ($this->orderHandler->isOrder($order)) {
                    //Init values to request payment
                    $amount = $order->getGrandTotal();
                    $currency = $order->getOrderCurrencyCode();
                    $reference = $order->getIncrementId();

                    // Get response and success
                    $response = $this->requestPayment($amount, $currency, $reference);

                    // Get the store code
                    $storeCode = $this->storeManager->getStore()->getCode();

                    // Process the response
                    $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
                    if ($api->isValidResponse($response)) {
                        // From now on the order must not be rolled back: the gateway accepted the payment
                        $this->isPaymentAccepted = true;

                        // Add the payment info to the order
                        $order = $this->utilities->setPaymentData($order, $response);

                        // Save the order
                        $this->orderRepository->save($order);

                        // Update the response parameters
                        $success = true;
                        $orderId = $order->getId();
                    } else {
                        // Cancel or delete the order according to the configuration
                        $this->apiOrderRollback->rollback($order);

                        $errorMessage = __('The payment request was declined by the gateway.');
                    }
                } else {
                    $errorMessage = __('The order could not be created.');
                }
            } else {
                $errorMessage = __('The request is invalid.');
            }
        } catch (Throwable $exception) {
            $orderId = $this->handleProcessingError($exception, $order);
            $errorMessage = $exception instanceof LocalizedException
                ? $exception->getMessage()
                : __('The order could not be processed.');
        }

        // Return the json response
        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'order_id' => $orderId,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Roll back the order after an unexpected error, return the id of the order kept if any
     *
     * @param Throwable $exception
     * @param OrderInterface|null $order
     *
     * @return int
     */
    private function handleProcessingError(Throwable $exception, ?OrderInterface $order): int
    {
        // The order may have been placed even if the error occurred while creating it
        if (!$this->orderHandler->isOrder($order)) {
            $order = $this->apiOrderRollback->findQuoteOrder($this->quote);
        }

        if ($this->isPaymentAccepted) {
            // Never cancel an order whose payment went through: keep it for a manual review
            $this->logger->critical(
                'Checkout.com API V1 payment accepted but order processing failed, order '
                . ($order ? $order->getIncrementId() : 'unknown') . ' needs a manual review',
                ['exception' => $exception]
            );

            return $order ? (int)$order->getId() : 0;
        }

        if (!$exception instanceof LocalizedException) {
            $this->logger->error('Checkout.com API V1 payment processing failed', ['exception' => $exception]);
        }

        if ($this->orderHandler->isOrder($order)) {
            try {
                if (!$this->apiOrderRollback->rollbackOnError($order)) {
                    $this->logger->critical(
                        'Checkout.com API V1 could not cancel order ' . $order->getIncrementId()
                    );
                }
            } catch (Throwable $rollbackException) {
                $this->logger->critical(
                    'Checkout.com API V1 could not roll back order ' . $order->getIncrementId(),
                    ['exception' => $rollbackException]
                );
            }
        }

        return 0;
    }

    /**
     * Check if the request is valid
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function isValidRequest(): bool
    {
        return $this->config->isValidAuth('pk') && $this->dataIsValid();
    }

    /**
     * Check if the data is valid
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function dataIsValid(): bool
    {
        // Check the quote ID (numeric id or masked id)
        if (!is_object($this->data) || !isset($this->data->quote_id) || empty((string)$this->data->quote_id)) {
            throw new LocalizedException(
                __('The quote ID is missing or invalid.')
            );
        }

        // Check the payment token
        if (!isset($this->data->payment_token) || empty($this->data->payment_token)) {
            throw new LocalizedException(
                __('The payment token is missing or invalid.')
            );
        }

        return true;
    }

    /**
     * Load the quote
     *
     * @return CartInterface
     * @throws LocalizedException
     */
    protected function loadQuote(): CartInterface
    {
        // Load the quote owned by the caller (masked id required for guests)
        $quote = $this->apiQuoteResolver->resolve(
            $this->data->quote_id,
            $this->customerSession->isLoggedIn() ? (int)$this->customerSession->getCustomerId() : null
        );

        // Handle a quote not found
        if ($quote === null) {
            throw new LocalizedException(
                __('No quote was found with the provided ID.')
            );
        }

        // Only a Checkout.com method available for the quote can place an order
        if (!$this->apiPaymentMethodGuard->isAllowed(self::PAYMENT_METHOD, $quote)) {
            throw new LocalizedException(
                __('The payment method is not available.')
            );
        }

        return $quote;
    }

    /**
     * Request payment to API handler
     *
     * @param float $amount
     * @param string $currencyCode
     * @param string $reference
     *
     * @return mixed
     */
    protected function requestPayment(float $amount, string $currencyCode, string $reference)
    {
        // Prepare the payment request payload
        $payload = [
            'cardToken' => $this->data->payment_token,
        ];

        if (isset($this->data->card_bin)) {
            $payload['cardBin'] = $this->data->card_bin;
        }

        // Send the charge request
        return $this->methodHandler->get(self::PAYMENT_METHOD)->sendPaymentRequest(
            $payload,
            $amount,
            $currencyCode,
            $reference
        );
    }
}
