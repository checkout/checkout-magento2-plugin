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

namespace CheckoutCom\Magento2\Model\Api;

use CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;
use CheckoutCom\Magento2\Api\Data\PaymentResponseInterface;
use CheckoutCom\Magento2\Api\V3Interface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Api\Data\PaymentResponseFactory;
use CheckoutCom\Magento2\Model\Service\ApiOrderRollback;
use CheckoutCom\Magento2\Model\Service\ApiPaymentMethodGuard;
use CheckoutCom\Magento2\Model\Service\ApiPaymentSessionGuard;
use CheckoutCom\Magento2\Model\Service\ApiQuoteResolver;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class V3 - Execute the API v3 endpoint
 */
class V3 implements V3Interface
{
    /**
     * Methods whose payment request can be built from the V3 payload (card token or saved card public hash)
     */
    private const SUPPORTED_PAYMENT_METHODS = [
        'checkoutcom_card_payment',
        'checkoutcom_vault',
    ];

    public $customer;
    public $data;
    public $api;
    public $result;
    public $quote;
    private bool $isPaymentAccepted = false;
    private PaymentResponseFactory $paymentResponseFactory;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private QuoteHandlerService $quoteHandler;
    private OrderHandlerService $orderHandler;
    private OrderStatusHandlerService $orderStatusHandler;
    private MethodHandlerService $methodHandler;
    private ApiHandlerService $apiHandler;
    private PaymentErrorHandlerService $paymentErrorHandler;
    private Utilities $utilities;
    private VaultHandlerService $vaultHandler;
    private Http $request;
    private OrderRepositoryInterface $orderRepository;
    private ApiQuoteResolver $apiQuoteResolver;
    private ApiPaymentSessionGuard $apiPaymentSessionGuard;
    private ApiPaymentMethodGuard $apiPaymentMethodGuard;
    private ApiOrderRollback $apiOrderRollback;
    private LoggerInterface $logger;

    public function __construct(
        PaymentResponseFactory $paymentResponseFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        PaymentErrorHandlerService $paymentErrorHandler,
        Utilities $utilities,
        VaultHandlerService $vaultHandler,
        Http $request,
        OrderRepositoryInterface $orderRepository,
        ApiQuoteResolver $apiQuoteResolver,
        ApiPaymentSessionGuard $apiPaymentSessionGuard,
        ApiPaymentMethodGuard $apiPaymentMethodGuard,
        ApiOrderRollback $apiOrderRollback,
        LoggerInterface $logger
    ) {
        $this->paymentResponseFactory = $paymentResponseFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->utilities = $utilities;
        $this->vaultHandler = $vaultHandler;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->apiQuoteResolver = $apiQuoteResolver;
        $this->apiPaymentSessionGuard = $apiPaymentSessionGuard;
        $this->apiPaymentMethodGuard = $apiPaymentMethodGuard;
        $this->apiOrderRollback = $apiOrderRollback;
        $this->logger = $logger;
    }

    /**
     * Description executeApiV3 function
     *
     * @param CustomerInterface $customer
     * @param PaymentRequestInterface $paymentRequest
     *
     * @return PaymentResponseInterface
     * @throws NoSuchEntityException|LocalizedException
     */
    public function executeApiV3(
        CustomerInterface $customer,
        PaymentRequestInterface $paymentRequest
    ): PaymentResponseInterface {
        // Assign the customer and payment request to be accessible to the whole class
        $this->customer = $customer;
        $this->data = $paymentRequest;

        // Prepare the V3 object
        return $this->execute();
    }

    /**
     * Description executeGuestApiV3 function
     *
     * @param PaymentRequestInterface $paymentRequest
     *
     * @return PaymentResponseInterface
     * @throws NoSuchEntityException|LocalizedException
     */
    public function executeGuestApiV3(
        PaymentRequestInterface $paymentRequest
    ): PaymentResponseInterface {
        // Assign the payment request to be accessible to the whole class
        $this->data = $paymentRequest;

        // Prepare the V3 object
        return $this->execute();
    }

    /**
     * Get an API handler instance and the request data
     *
     * @return PaymentResponseInterface
     * @throws NoSuchEntityException|LocalizedException
     */
    private function execute(): PaymentResponseInterface
    {
        // Get an API handler instance
        $this->api = $this->apiHandler->init(
            $this->storeManager->getStore()->getCode(),
            ScopeInterface::SCOPE_STORE
        );

        // Prepare the default response
        $this->result = [
            'success' => false,
            'order_id' => 0,
            'redirect_url' => '',
            'error_message' => [],
        ];

        // Validate the public key
        if ($this->isValidPublicKey()) {
            if ($this->hasValidFields()) {
                $this->result = $this->processPayment();
                if (!$this->result['success'] && !$this->result['error_message']) {
                    $this->result['error_message'][] = __('The order could not be created.');
                }
            }
        } else {
            $this->result['error_message'][] = __('The public key is invalid.');
        }

        // Set the payment response details
        $responseDetails = $this->paymentResponseFactory->create();
        $responseDetails->setSuccess($this->result['success']);
        $responseDetails->setOrderId($this->result['order_id']);
        $responseDetails->setRedirectUrl($this->result['redirect_url']);
        $responseDetails->setErrorMessage($this->result['error_message']);

        return $responseDetails;
    }

    /**
     * Check if the request is valid
     *
     * @return bool
     */
    private function isValidPublicKey(): bool
    {
        return $this->config->isValidAuth('pk', 'Cko-Authorization');
    }

    /**
     * Process the payment request and handle the response.
     * Any error raised once the order exists rolls the order back, so no order is left without payment.
     *
     * @return mixed[]
     */
    private function processPayment(): array
    {
        $order = null;

        try {
            // Create order if needed before payment
            $order = $this->createOrder($this->data->getPaymentMethod());

            if ($this->orderHandler->isOrder($order)) {
                $this->payOrder($order);
            }
        } catch (Throwable $exception) {
            $this->handleProcessingError($exception, $order);
        }

        return $this->result;
    }

    /**
     * Request the payment of the order and update it with the response
     *
     * @param OrderInterface $order
     *
     * @return void
     * @throws LocalizedException
     */
    private function payOrder(OrderInterface $order): void
    {
        //Init values to request payment
        $amount = $order->getGrandTotal();
        $currency = $order->getOrderCurrencyCode();
        $reference = $order->getIncrementId();

        // Get the payment
        $response = $this->getPaymentResponse($order, $amount, $currency, $reference);
        $isValidResponse = $this->api->isValidResponse($response);

        if ($isValidResponse) {
            // From now on the order must not be rolled back: the gateway accepted the payment
            $this->isPaymentAccepted = true;

            // Process the payment response
            $is3ds = isset($response['_links']['redirect']['href']);

            // Add the payment info to the order
            $order = $this->utilities->setPaymentData($order, $response);

            // Save the order
            $this->orderRepository->save($order);

            // Use custom redirect urls
            if ($is3ds) {
                $this->result['redirect_url'] = $response['_links']['redirect']['href'];
            } elseif ($this->data->getSuccessUrl()) {
                $this->result['redirect_url'] = $this->data->getSuccessUrl();
            }

            // Update the result
            $this->result['success'] = $isValidResponse;
        } else {
            // Payment failed
            if (isset($response['response_code'])) {
                $this->result['error_message'][] = $this->paymentErrorHandler->getErrorMessage(
                    $response['response_code']
                );
            }

            // Cancel or delete the order according to the configuration
            $this->apiOrderRollback->rollback($order);

            //  Token invalid/expired
            if (is_object($response) && method_exists($response, 'getErrors')) {
                $this->result['error_message'] = array_merge(
                    $this->result['error_message'],
                    $response->getErrors()
                );
            }

            if ($this->data->getFailureUrl()) {
                $this->result['redirect_url'] = $this->data->getFailureUrl();
            }
        }

        // Update the order id
        $this->result['order_id'] = $order->getIncrementId();
    }

    /**
     * Roll back the order after an unexpected error and return a generic error
     *
     * @param Throwable $exception
     * @param OrderInterface|null $order
     *
     * @return void
     */
    private function handleProcessingError(Throwable $exception, ?OrderInterface $order): void
    {
        // The order may have been placed even if the error occurred while creating it
        if (!$this->orderHandler->isOrder($order)) {
            $order = $this->apiOrderRollback->findQuoteOrder($this->quote);
        }

        if ($this->isPaymentAccepted) {
            // Never cancel an order whose payment went through: keep it for a manual review
            $this->logger->critical(
                'Checkout.com API V3 payment accepted but order processing failed, order '
                . ($order ? $order->getIncrementId() : 'unknown') . ' needs a manual review',
                ['exception' => $exception]
            );
        } else {
            $this->logger->error('Checkout.com API V3 payment processing failed', ['exception' => $exception]);
        }

        if (!$this->isPaymentAccepted && $this->orderHandler->isOrder($order)) {
            try {
                if (!$this->apiOrderRollback->rollbackOnError($order)) {
                    $this->logger->critical(
                        'Checkout.com API V3 could not cancel order ' . $order->getIncrementId()
                    );
                }
            } catch (Throwable $rollbackException) {
                $this->logger->critical(
                    'Checkout.com API V3 could not roll back order ' . $order->getIncrementId(),
                    ['exception' => $rollbackException]
                );
            }
        }

        $this->result['success'] = false;
        $this->result['order_id'] = $this->isPaymentAccepted && $order ? $order->getIncrementId() : 0;
        $this->result['redirect_url'] = '';
        $this->result['error_message'][] = __('The order could not be processed.');
    }

    /**
     * Create an order
     *
     * @param string $methodId
     *
     * @return AbstractExtensibleModel|OrderInterface|mixed|object|null
     * @throws LocalizedException
     */
    private function createOrder(string $methodId): ?OrderInterface
    {
        // Load the quote
        $this->quote = $this->loadQuote();
        $order = null;

        if ($this->quote) {
            // Only a Checkout.com method available for the quote can place an order
            if (!$this->apiPaymentMethodGuard->isAllowed($methodId, $this->quote)) {
                $this->result['error_message'][] = __('The payment method is not available.');

                return null;
            }

            // Create an order
            $order = $this->orderHandler->setMethodId($methodId)->handleOrder($this->quote);
        }

        return $order;
    }

    /**
     * Load the quote
     *
     * @return DataObject|CartInterface|Quote|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function loadQuote(): ?CartInterface
    {
        // Load the quote owned by the caller (masked id required for guests)
        $quote = $this->apiQuoteResolver->resolve(
            $this->data->getQuoteId(),
            isset($this->customer) ? (int)$this->customer->getId() : null
        );

        // Handle a quote not found
        if ($quote === null) {
            $this->result['error_message'][] = __('No quote found with the provided ID');
        }

        return $quote;
    }

    /**
     * Get a payment response
     *
     * @param OrderInterface $order
     * @param float $amount
     * @param string $currencyCode
     * @param string $reference
     *
     * @return mixed
     */
    private function getPaymentResponse(OrderInterface $order, float $amount, string $currencyCode, string $reference)
    {
        $sessionId = $this->request->getParam('cko-session-id');

        if (!$sessionId) {
            return $this->requestPayment($amount, $currencyCode, $reference);
        }

        // Only reuse an existing payment if it was made for this very order
        $response = $this->api->getPaymentDetails((string)$sessionId);
        if (!$this->apiPaymentSessionGuard->matchesOrder($response, $order)) {
            $this->result['error_message'][] = __('The payment does not match the order.');

            return [];
        }

        return $response;
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
    private function requestPayment(float $amount, string $currencyCode, string $reference)
    {
        // Prepare the payment request payload
        $payload = [];

        // Set the card bin
        if (!empty($this->data->getCardBin())) {
            $payload['cardBin'] = $this->data->getCardBin();
        }

        // Add card specific details to the payment request
        if ($this->data->getPaymentMethod() == 'checkoutcom_card_payment') {
            // Add the card token to the request
            $payload['cardToken'] = $this->data->getPaymentToken();

            // Prepare the save card setting
            $saveCardEnabled = $this->config->getValue('save_card_option', 'checkoutcom_card_payment');

            if ($this->data->getSaveCard() !== null && $this->data->getSaveCard() === true && $saveCardEnabled && isset($this->customer)) {
                $payload['saveCard'] = true;
            }
        }

        // Add vault specific details to the payment request
        if ($this->data->getPaymentMethod() == 'checkoutcom_vault') {
            // Set the public hash - Only for vault method
            if (!empty($this->data->getPublicHash())) {
                $payload['publicHash'] = $this->data->getPublicHash();
            }

            if ($this->config->getValue('require_cvv', 'checkoutcom_vault')) {
                $payload['cvv'] = $this->data->getCardCvv();
            }
        }

        // Set the success URL
        if (!empty($this->data->getSuccessUrl())) {
            $payload['successUrl'] = $this->data->getSuccessUrl();
        }

        // Set the failure URL
        if (!empty($this->data->getFailureUrl())) {
            $payload['failureUrl'] = $this->data->getFailureUrl();
        }

        // Send the charge request
        return $this->methodHandler->get($this->data->getPaymentMethod())->sendPaymentRequest(
            $payload,
            $amount,
            $currencyCode,
            $reference,
            $this->quote,
            true,
            $this->customer ? $this->customer->getId() : null
        );
    }

    /**
     * Description hasValidFields function
     *
     * @return bool
     */
    private function hasValidFields(): bool
    {
        $isValid = true;

        // Check that the payment method has been specified
        if ($this->data->getPaymentMethod() !== null) {
            if (!is_string($this->data->getPaymentMethod())) {
                $this->result['error_message'][] = __('Payment method provided is not a string');
                $isValid = false;
            } elseif ($this->data->getPaymentMethod() == '') {
                $this->result['error_message'][] = __('Payment method provided is empty string');
                $isValid = false;
            } elseif (!in_array($this->data->getPaymentMethod(), self::SUPPORTED_PAYMENT_METHODS, true)) {
                $this->result['error_message'][] = __('The payment method is not available.');
                $isValid = false;
            }
        } else {
            $this->result['error_message'][] = __('Payment method is missing from request body');
            $isValid = false;
        }

        // Check the quote id has been specified correctly
        if ($this->data->getQuoteId() !== null) {
            if (is_int($this->data->getQuoteId()) && $this->data->getQuoteId() < 1) {
                $this->result['error_message'][] = __('Quote ID provided must be a positive integer');
                $isValid = false;
            }
        } else {
            $this->result['error_message'][] = __('Quote ID is missing from request body');
            $isValid = false;
        }

        // Check the card bin has been specified correctly
        if ($this->data->getCardBin() !== null) {
            if ($this->data->getCardBin() == '') {
                $this->result['error_message'][] = __('Card BIN is empty string');
                $isValid = false;
            }
        }

        // Check the success url has been specified correctly
        if ($this->data->getSuccessUrl() !== null) {
            if (!is_string($this->data->getSuccessUrl())) {
                $this->result['error_message'][] = __('Success URL provided is not a string');
                $isValid = false;
            } elseif ($this->data->getSuccessUrl() == '') {
                $this->result['error_message'][] = __('Success URL is empty string');
                $isValid = false;
            }
        }

        // Check the failure url has been specified correctly
        if ($this->data->getFailureUrl() !== null) {
            if (!is_string($this->data->getFailureUrl())) {
                $this->result['error_message'][] = __('Failure URL provided is not a string');
                $isValid = false;
            } elseif ($this->data->getFailureUrl() == '') {
                $this->result['error_message'][] = __('Failure URL is empty string');
                $isValid = false;
            }
        }

        // CKO card payment method specific validation
        if ($this->data->getPaymentMethod() == 'checkoutcom_card_payment') {
            // Check the payment method is active
            if (!$this->config->getValue('active', 'checkoutcom_card_payment')) {
                $this->result['error_message'][] = __('Card payment method is not active');
                $isValid = false;
            }

            // Check the payment token has been specified correctly
            if ($this->data->getPaymentToken() !== null) {
                if (!is_string($this->data->getPaymentToken())) {
                    $this->result['error_message'][] = __('Payment token provided is not a string');
                    $isValid = false;
                } elseif ($this->data->getPaymentToken() == '') {
                    $this->result['error_message'][] = __('Payment token provided is empty string');
                    $isValid = false;
                }
            } else {
                $this->result['error_message'][] = __('Payment token is missing from request body');
                $isValid = false;
            }
        }

        // CKO vault payment method specific validation
        if ($this->data->getPaymentMethod() === 'checkoutcom_vault' && isset($this->customer)) {
            // Check the payment method is active
            if (!$this->config->getValue('active', 'checkoutcom_vault')) {
                $this->result['error_message'][] = __('Vault payment method is not active');
                $isValid = false;
            }

            // Public hash error messages
            if ($this->data->getPublicHash() !== null) {
                if (!is_string($this->data->getPublicHash())) {
                    $this->result['error_message'][] = __('Public hash provided is not a string');
                    $isValid = false;
                } elseif ($this->data->getPublicHash() == '') {
                    $this->result['error_message'][] = __('Public hash provided is empty string');
                    $isValid = false;
                } elseif ($this->vaultHandler->getCardFromHash(
                        $this->data->getPublicHash(),
                        $this->customer->getId()
                    ) == null
                ) {
                    $this->result['error_message'][] = __('Public hash provided is not valid');
                    $isValid = false;
                }
            } else {
                $this->result['error_message'][] = __('Public hash is missing from request body');
                $isValid = false;
            }

            // Check the card cvv has been specified correctly
            if ($this->config->getValue('require_cvv', 'checkoutcom_vault')) {
                if ($this->data->getCardCvv() == null || (int)$this->data->getCardCvv() == 0) {
                    $this->result['error_message'][] = __('CVV value is required');
                    $isValid = false;
                }
            } else {
                if ($this->data->getCardCvv()) {
                    $this->result['error_message'][] = __('CVV value is not required');
                    $isValid = false;
                }
            }
        } elseif ($this->data->getPaymentMethod() == 'checkoutcom_vault' && !isset($this->customer)) {
            $this->result['error_message'][] = __('Vault payment method is not available for guest checkouts.');
            $isValid = false;
        }

        return $isValid;
    }
}
