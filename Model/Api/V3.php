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

namespace CheckoutCom\Magento2\Model\Api;

use CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;
use CheckoutCom\Magento2\Api\Data\PaymentResponseInterface;
use CheckoutCom\Magento2\Api\V3Interface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Api\Data\PaymentResponseFactory;
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
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class V3 - Execute the API v3 endpoint
 */
class V3 implements V3Interface
{
    /**
     * $paymentResponseFactory field
     *
     * @var PaymentResponseFactory $paymentResponseFactory
     */
    private $paymentResponseFactory;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $quoteIdMaskFactory field
     *
     * @var QuoteIdMaskFactory $quoteIdMaskFactory
     */
    private $quoteIdMaskFactory;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * $orderStatusHandler field
     *
     * @var OrderStatusHandlerService $orderStatusHandler
     */
    private $orderStatusHandler;
    /**
     * $methodHandler field
     *
     * @var MethodHandlerService $methodHandler
     */
    private $methodHandler;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;
    /**
     * $paymentErrorHandler field
     *
     * @var PaymentErrorHandlerService $paymentErrorHandler
     */
    private $paymentErrorHandler;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    private $vaultHandler;
    /**
     * $request
     *
     * @var Http $request
     */
    private $request;
    /**
     * $data field
     *
     * @var PaymentRequestInterface $data
     */
    private $data;
    /**
     * $customer field
     *
     * @var CustomerInterface $customer
     */
    private $customer;
    /**
     * $result field
     *
     * @var array $result
     */
    private $result;
    /**
     * $api field
     *
     * @var Object $api
     */
    private $api;
    /**
     * $order field
     *
     * @var Object $order
     */
    private $order;
    /**
     * $quote field
     *
     * @var Object $quote
     */
    private $quote;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;
    /**
     * $quoteIdMaskResource field
     *
     * @var QuoteIdMaskResource $quoteIdMaskResource
     */
    private $quoteIdMaskResource;

    /**
     * V3 constructor
     *
     * @param PaymentResponseFactory     $paymentResponseFactory
     * @param Config                     $config
     * @param StoreManagerInterface      $storeManager
     * @param QuoteHandlerService        $quoteHandler
     * @param QuoteIdMaskFactory         $quoteIdMaskFactory
     * @param OrderHandlerService        $orderHandler
     * @param OrderStatusHandlerService  $orderStatusHandler
     * @param MethodHandlerService       $methodHandler
     * @param ApiHandlerService          $apiHandler
     * @param PaymentErrorHandlerService $paymentErrorHandler
     * @param Utilities                  $utilities
     * @param VaultHandlerService        $vaultHandler
     * @param Http                       $request
     * @param OrderRepositoryInterface   $orderRepository
     * @param QuoteIdMaskResource        $quoteIdMaskResource
     */
    public function __construct(
        PaymentResponseFactory $paymentResponseFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        PaymentErrorHandlerService $paymentErrorHandler,
        Utilities $utilities,
        VaultHandlerService $vaultHandler,
        Http $request,
        OrderRepositoryInterface $orderRepository,
        QuoteIdMaskResource $quoteIdMaskResource
    ) {
        $this->paymentResponseFactory = $paymentResponseFactory;
        $this->config                 = $config;
        $this->storeManager           = $storeManager;
        $this->quoteHandler           = $quoteHandler;
        $this->quoteIdMaskFactory     = $quoteIdMaskFactory;
        $this->orderHandler           = $orderHandler;
        $this->orderStatusHandler     = $orderStatusHandler;
        $this->methodHandler          = $methodHandler;
        $this->apiHandler             = $apiHandler;
        $this->paymentErrorHandler    = $paymentErrorHandler;
        $this->utilities              = $utilities;
        $this->vaultHandler           = $vaultHandler;
        $this->request                = $request;
        $this->orderRepository        = $orderRepository;
        $this->quoteIdMaskResource    = $quoteIdMaskResource;
    }

    /**
     * Description executeApiV3 function
     *
     * @param CustomerInterface       $customer
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
        $this->data     = $paymentRequest;

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
            $this->storeManager->getStore()->getCode()
        );

        // Prepare the default response
        $this->result = [
            'success'       => false,
            'order_id'      => 0,
            'redirect_url'  => '',
            'error_message' => [],
        ];

        // Validate the public key
        if ($this->isValidPublicKey()) {
            if ($this->hasValidFields()) {
                $this->result = $this->processPayment();
                if (!$this->result['success']) {
                    $this->result['error_message'][] = __('The order could not be created.');
                    // Handle order on failed payment
                    $this->orderStatusHandler->handleFailedPayment($this->order);
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
     *
     * @return mixed[]
     * @throws LocalizedException
     */
    private function processPayment(): array
    {
        $order = $this->createOrder($this->data->getPaymentMethod());
        if ($this->orderHandler->isOrder($order)) {
            $this->order = $order;
            // Get the payment response
            $response = $this->getPaymentResponse($order);

            if ($this->api->isValidResponse($response)) {
                // Process the payment response
                $is3ds = property_exists($response, '_links')
                    && isset($response->_links['redirect'])
                    && isset($response->_links['redirect']['href']);

                // Add the payment info to the order
                $order = $this->utilities->setPaymentData($order, $response);

                // Save the order
                $this->orderRepository->save($order);

                // Use custom redirect urls
                if ($is3ds) {
                    $this->result['redirect_url'] = $response->_links['redirect']['href'];
                } elseif ($this->data->getSuccessUrl()) {
                    $this->result['redirect_url'] = $this->data->getSuccessUrl();
                }

                // Update the result
                $this->result['success'] = $response->isSuccessful();
            } else {
                // Payment failed
                if (isset($response->response_code)) {
                    $this->result['error_message'][] = $this->paymentErrorHandler->getErrorMessage(
                        $response->response_code
                    );
                }

                //  Token invalid/expired
                if (method_exists($response, 'getErrors')) {
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

        return $this->result;
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
        $order       = null;

        if ($this->quote) {
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
        // Convert masked quote ID hash to quote ID int
        if (preg_match("/([A-Za-z])\w+/", $this->data->getQuoteId())) {
            /** @var QuoteIdMask $quoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $this->quoteIdMaskResource->load($quoteIdMask, $this->data->getQuoteId(), 'masked_id');
            $this->data->setQuoteId($quoteIdMask->getQuoteId());
        }

        // Load the quote
        $quote = $this->quoteHandler->getQuote([
            'entity_id' => $this->data->getQuoteId(),
        ]);

        // Handle a quote not found
        if (!$this->quoteHandler->isQuote($quote)) {
            $this->result['error_message'][] = __('No quote found with the provided ID');
            $quote                           = null;
        }

        return $quote;
    }

    /**
     * Get a payment response
     *
     * @param OrderInterface $order
     *
     * @return mixed
     */
    private function getPaymentResponse(OrderInterface $order)
    {
        $sessionId = $this->request->getParam('cko-session-id');

        return ($sessionId && !empty($sessionId)) ? $this->api->getPaymentDetails($sessionId) : $this->requestPayment(
            $order
        );
    }

    /**
     * Request payment to API handler
     *
     * @param OrderInterface $order
     *
     * @return mixed
     */
    private function requestPayment(OrderInterface $order)
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

            if ($this->data->getSaveCard() !== null && $this->data->getSaveCard(
                ) === true && $saveCardEnabled && isset($this->customer)) {
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
            $order->getGrandTotal(),
            $order->getOrderCurrencyCode(),
            $order->getIncrementId(),
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
                $isValid                         = false;
            } elseif ($this->data->getPaymentMethod() == '') {
                $this->result['error_message'][] = __('Payment method provided is empty string');
                $isValid                         = false;
            }
        } else {
            $this->result['error_message'][] = __('Payment method is missing from request body');
            $isValid                         = false;
        }

        // Check the quote id has been specified correctly
        if ($this->data->getQuoteId() !== null) {
            if (is_int($this->data->getQuoteId()) && $this->data->getQuoteId() < 1) {
                $this->result['error_message'][] = __('Quote ID provided must be a positive integer');
                $isValid                         = false;
            }
        } else {
            $this->result['error_message'][] = __('Quote ID is missing from request body');
            $isValid                         = false;
        }

        // Check the card bin has been specified correctly
        if ($this->data->getCardBin() !== null) {
            if ($this->data->getCardBin() == '') {
                $this->result['error_message'][] = __('Card BIN is empty string');
                $isValid                         = false;
            }
        }

        // Check the success url has been specified correctly
        if ($this->data->getSuccessUrl() !== null) {
            if (!is_string($this->data->getSuccessUrl())) {
                $this->result['error_message'][] = __('Success URL provided is not a string');
                $isValid                         = false;
            } elseif ($this->data->getSuccessUrl() == '') {
                $this->result['error_message'][] = __('Success URL is empty string');
                $isValid                         = false;
            }
        }

        // Check the failure url has been specified correctly
        if ($this->data->getFailureUrl() !== null) {
            if (!is_string($this->data->getFailureUrl())) {
                $this->result['error_message'][] = __('Failure URL provided is not a string');
                $isValid                         = false;
            } elseif ($this->data->getFailureUrl() == '') {
                $this->result['error_message'][] = __('Failure URL is empty string');
                $isValid                         = false;
            }
        }

        // CKO card payment method specific validation
        if ($this->data->getPaymentMethod() == 'checkoutcom_card_payment') {
            // Check the payment method is active
            if (!$this->config->getValue('active', 'checkoutcom_card_payment')) {
                $this->result['error_message'][] = __('Card payment method is not active');
                $isValid                         = false;
            }

            // Check the payment token has been specified correctly
            if ($this->data->getPaymentToken() !== null) {
                if (!is_string($this->data->getPaymentToken())) {
                    $this->result['error_message'][] = __('Payment token provided is not a string');
                    $isValid                         = false;
                } elseif ($this->data->getPaymentToken() == '') {
                    $this->result['error_message'][] = __('Payment token provided is empty string');
                    $isValid                         = false;
                }
            } else {
                $this->result['error_message'][] = __('Payment token is missing from request body');
                $isValid                         = false;
            }
        }

        // CKO vault payment method specific validation
        if ($this->data->getPaymentMethod() === 'checkoutcom_vault' && isset($this->customer)) {
            // Check the payment method is active
            if (!$this->config->getValue('active', 'checkoutcom_vault')) {
                $this->result['error_message'][] = __('Vault payment method is not active');
                $isValid                         = false;
            }

            // Public hash error messages
            if ($this->data->getPublicHash() !== null) {
                if (!is_string($this->data->getPublicHash())) {
                    $this->result['error_message'][] = __('Public hash provided is not a string');
                    $isValid                         = false;
                } elseif ($this->data->getPublicHash() == '') {
                    $this->result['error_message'][] = __('Public hash provided is empty string');
                    $isValid                         = false;
                } elseif ($this->vaultHandler->getCardFromHash(
                        $this->data->getPublicHash(),
                        $this->customer->getId()
                    ) == null) {
                    $this->result['error_message'][] = __('Public hash provided is not valid');
                    $isValid                         = false;
                }
            } else {
                $this->result['error_message'][] = __('Public hash is missing from request body');
                $isValid                         = false;
            }

            // Check the card cvv has been specified correctly
            if ($this->config->getValue('require_cvv', 'checkoutcom_vault')) {
                if ($this->data->getCardCvv() == null || (int)$this->data->getCardCvv() == 0) {
                    $this->result['error_message'][] = __('CVV value is required');
                    $isValid                         = false;
                }
            } else {
                if ($this->data->getCardCvv()) {
                    $this->result['error_message'][] = __('CVV value is not required');
                    $isValid                         = false;
                }
            }
        } elseif ($this->data->getPaymentMethod() == 'checkoutcom_vault' && !isset($this->customer)) {
            $this->result['error_message'][] = __('Vault payment method is not available for guest checkouts.');
            $isValid                         = false;
        }

        return $isValid;
    }
}
