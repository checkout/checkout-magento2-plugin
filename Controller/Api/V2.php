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

use Checkout\CheckoutArgumentException;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\ApiOrderRollback;
use CheckoutCom\Magento2\Model\Service\ApiPaymentMethodGuard;
use CheckoutCom\Magento2\Model\Service\ApiPaymentSessionGuard;
use CheckoutCom\Magento2\Model\Service\ApiQuoteResolver;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
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
 * Class V2
 */
class V2 extends Action
{
    private const PAYMENT_METHOD = 'checkoutcom_card_payment';

    /**
     * @var array|mixed|mixed[]
     */
    public $quote;
    protected $result;
    protected $order;
    protected $data;
    protected ApiHandlerService $api;
    private bool $isPaymentAccepted = false;
    private JsonFactory $jsonFactory;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private QuoteHandlerService $quoteHandler;
    private OrderHandlerService $orderHandler;
    private OrderStatusHandlerService $orderStatusHandler;
    private MethodHandlerService $methodHandler;
    private ApiHandlerService $apiHandler;
    private PaymentErrorHandlerService $paymentErrorHandler;
    private Utilities $utilities;
    private OrderRepositoryInterface $orderRepository;
    private ApiQuoteResolver $apiQuoteResolver;
    private ApiPaymentSessionGuard $apiPaymentSessionGuard;
    private CustomerSession $customerSession;
    private ApiPaymentMethodGuard $apiPaymentMethodGuard;
    private ApiOrderRollback $apiOrderRollback;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        PaymentErrorHandlerService $paymentErrorHandler,
        Utilities $utilities,
        OrderRepositoryInterface $orderRepository,
        ApiQuoteResolver $apiQuoteResolver,
        ApiPaymentSessionGuard $apiPaymentSessionGuard,
        CustomerSession $customerSession,
        ApiPaymentMethodGuard $apiPaymentMethodGuard,
        ApiOrderRollback $apiOrderRollback,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->utilities = $utilities;
        $this->orderRepository = $orderRepository;
        $this->apiQuoteResolver = $apiQuoteResolver;
        $this->apiPaymentSessionGuard = $apiPaymentSessionGuard;
        $this->customerSession = $customerSession;
        $this->apiPaymentMethodGuard = $apiPaymentMethodGuard;
        $this->apiOrderRollback = $apiOrderRollback;
        $this->logger = $logger;
    }

    /**
     * Handles the controller method
     *
     * @return Json
     * @throws NoSuchEntityException|LocalizedException
     */
    public function execute(): Json
    {
        // Prepare the V2 object
        $this->init();

        // Process the payment
        if ($this->isValidPublicKey()) {
            if ($this->hasValidFields()) {
                $this->result = $this->processPayment();
                if (!$this->result['success']) {
                    $this->result['error_message'][] = __('The order could not be created.');
                }
            }
        } else {
            $this->result['error_message'][] = __('The public key is invalid.');
        }

        // Return the json response
        return $this->jsonFactory->create()->setData($this->result);
    }

    /**
     * Get an API handler instance and the request data
     *
     * @return void
     * @throws NoSuchEntityException|CheckoutArgumentException
     */
    public function init(): void
    {
        // Get the request parameters
        $this->data = json_decode($this->getRequest()->getContent());

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
    }

    /**
     * Check if the request is valid
     *
     * @return bool|void
     */
    public function isValidPublicKey()
    {
        return $this->config->isValidAuth('pk');
    }

    /**
     * Description hasValidFields function
     *
     * @return bool
     */
    protected function hasValidFields()
    {
        $isValid = true;

        if (isset($this->data->payment_token)) {
            if (!is_string($this->data->payment_token)) {
                $this->result['error_message'][] = __('Payment token provided is not a string');
                $isValid = false;
            } elseif ($this->data->payment_token == '') {
                $this->result['error_message'][] = __('Payment token provided is empty string');
                $isValid = false;
            }
        } else {
            $this->result['error_message'][] = __('Payment token is missing from request body');
            $isValid = false;
        }

        if (isset($this->data->quote_id)) {
            if (is_int($this->data->quote_id) && $this->data->quote_id < 1) {
                $this->result['error_message'][] = __('Quote ID provided must be a positive integer');
                $isValid = false;
            }
        } else {
            $this->result['error_message'][] = __('Quote ID is missing from request body');
            $isValid = false;
        }

        if (isset($this->data->card_bin)) {
            if ($this->data->card_bin == '') {
                $this->result['error_message'][] = __('Card BIN is empty string');
                $isValid = false;
            }

            if (isset($this->data->success_url)) {
                if (!is_string($this->data->success_url)) {
                    $this->result['error_message'][] = __('Success URL provided is not a string');
                    $isValid = false;
                } elseif ($this->data->success_url == '') {
                    $this->result['error_message'][] = __('Success URL is empty string');
                    $isValid = false;
                }
            }

            if (isset($this->data->failure_url)) {
                if (!is_string($this->data->failure_url)) {
                    $this->result['error_message'][] = __('Failure URL provided is not a string');
                    $isValid = false;
                } elseif ($this->data->failure_url == '') {
                    $this->result['error_message'][] = __('Failure URL is empty string');
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }

    /**
     * Process the payment request and handle the response.
     * Any error raised once the order exists rolls the order back, so no order is left without payment.
     *
     * @return mixed[]
     */
    protected function processPayment(): array
    {
        $order = null;

        try {
            // Create Order if needed before payment
            $order = $this->createOrder();

            // Process the payment
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
    protected function payOrder(OrderInterface $order): void
    {
        //Init values to request payment
        $amount = $order->getGrandTotal();
        $currency = $order->getOrderCurrencyCode();
        $reference = $order->getIncrementId();

        // Get the payment response
        $response = $this->getPaymentResponse($amount, $currency, $reference, $order);

        if ($this->api->isValidResponse($response)) {
            // From now on the order must not be rolled back: the gateway accepted the payment
            $this->isPaymentAccepted = true;

            // Process the payment response
            if (isset($response['_links']['redirect']['href'])) {
                $this->result['redirect_url'] = $response['_links']['redirect']['href'];
            } elseif (isset($this->data->success_url)) {
                $this->result['redirect_url'] = $this->data->success_url;
            }

            // Add the payment info to the order
            $order = $this->utilities->setPaymentData($order, $response);

            // Save the order
            $this->orderRepository->save($order);

            // Update the result
            $this->result['success'] = true;
        } else {
            // Payment failed
            if (isset($response->response_code)) {
                $this->result['error_message'][] = $this->paymentErrorHandler->getErrorMessage(
                    $response->response_code
                );
            }

            //  Token invalid/expired
            if (is_object($response) && method_exists($response, 'getErrors')) {
                $this->result['error_message'] = array_merge(
                    $this->result['error_message'],
                    $response->getErrors()
                );
            }

            if (isset($this->data->failure_url)) {
                $this->result['redirect_url'] = $this->data->failure_url;
            }

            // Cancel or delete the order according to the configuration
            $this->apiOrderRollback->rollback($order);
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
    protected function handleProcessingError(Throwable $exception, ?OrderInterface $order): void
    {
        // The order may have been placed even if the error occurred while creating it
        if (!$this->orderHandler->isOrder($order)) {
            $order = $this->apiOrderRollback->findQuoteOrder($this->quote);
        }

        if ($this->isPaymentAccepted) {
            // Never cancel an order whose payment went through: keep it for a manual review
            $this->logger->critical(
                'Checkout.com API V2 payment accepted but order processing failed, order '
                . ($order ? $order->getIncrementId() : 'unknown') . ' needs a manual review',
                ['exception' => $exception]
            );
        } else {
            $this->logger->error('Checkout.com API V2 payment processing failed', ['exception' => $exception]);
        }

        if (!$this->isPaymentAccepted && $this->orderHandler->isOrder($order)) {
            try {
                $this->apiOrderRollback->rollback($order);
            } catch (Throwable $rollbackException) {
                $this->logger->critical(
                    'Checkout.com API V2 could not roll back order ' . $order->getIncrementId(),
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
     * Load the quote
     *
     * @return DataObject|CartInterface|Quote|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function loadQuote()
    {
        // Get the quote id
        if (!isset($this->data->quote_id)) {
            $this->data->quote_id = $this->data['quote_id'];
        }

        // Load the quote owned by the caller (masked id required for guests)
        $quote = $this->apiQuoteResolver->resolve(
            $this->data->quote_id,
            $this->customerSession->isLoggedIn() ? (int)$this->customerSession->getCustomerId() : null
        );

        // Handle a quote not found
        if ($quote === null) {
            $this->result['error_message'][] = __('No quote found with the provided ID');
        }

        return $quote;
    }

    /**
     * Get a payment response for cart.
     *
     * @param float $amount
     * @param string $currencyCode
     * @param string $reference
     * @param OrderInterface|null $order
     *
     * @return mixed
     */
    public function getPaymentResponse(
        float $amount,
        string $currencyCode,
        string $reference,
        ?OrderInterface $order = null
    ) {
        $sessionId = $this->getRequest()->getParam('cko-session-id');

        if (!$sessionId) {
            return $this->requestPayment($amount, $currencyCode, $reference);
        }

        // Only reuse an existing payment if it was made for this very order
        $response = $this->api->getPaymentDetails((string)$sessionId);
        if ($order === null || !$this->apiPaymentSessionGuard->matchesOrder($response, $order)) {
            $this->result['error_message'][] = __('The payment does not match the order.');

            return [];
        }

        return $response;
    }

    /**
     * Request payment to API handler by cart
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

        // Set the card bin
        if (isset($this->data->card_bin) && !empty($this->data->card_bin)) {
            $payload['cardBin'] = $this->data->card_bin;
        }

        // Set the success URL
        if (isset($this->data->success_url) && !empty($this->data->success_url)) {
            $payload['successUrl'] = $this->data->success_url;
        }

        // Set the failure URL
        if (isset($this->data->failure_url) && !empty($this->data->failure_url)) {
            $payload['failureUrl'] = $this->data->failure_url;
        }

        // Send the charge request
        return $this->methodHandler->get(self::PAYMENT_METHOD)->sendPaymentRequest(
            $payload,
            $amount,
            $currencyCode,
            $reference,
            $this->quote,
            true
        );
    }

    /**
     * Create an order
     *
     * @return AbstractExtensibleModel|OrderInterface|mixed|object|null
     * @throws LocalizedException
     */
    public function createOrder()
    {
        // Load the quote
        $this->quote = $this->loadQuote();
        $order = null;

        if ($this->quote) {
            // Only a Checkout.com method available for the quote can place an order
            if (!$this->apiPaymentMethodGuard->isAllowed(self::PAYMENT_METHOD, $this->quote)) {
                $this->result['error_message'][] = __('The payment method is not available.');

                return null;
            }

            // Create an order
            $order = $this->orderHandler->setMethodId(self::PAYMENT_METHOD)->handleOrder($this->quote);
        }

        return $order;
    }
}
