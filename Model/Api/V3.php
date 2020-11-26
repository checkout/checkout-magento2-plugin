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
 * @copyright 2010-2020 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Api;

/**
 * Class v3 - Execute the API v3 endpoint
 */
class V3 implements \CheckoutCom\Magento2\Api\V3Interface
{
    /**
     * @var paymentResponseFactory
     */
    private $paymentResponseFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var QuoteHandlerService
     */
    private $quoteHandler;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var OrderHandlerService
     */
    private $orderHandler;

    /**
     * @var OrderStatusHandlerService
     */
    private $orderStatusHandler;

    /**
     * @var MethodHandlerService
     */
    private $methodHandler;

    /**
     * @var ApiHandlerService
     */
    private $apiHandler;

    /*
     * @var PaymentErrorHandlerService
     */
    private $paymentErrorHandler;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * @var Http
     */
    public $request;
    
    /**
     * @var \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    private $data;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    private $customer;

    /**
     * @var Array
     */
    private $result;

    /**
     * @var Object
     */
    private $api;

    /**
     * @var Object
     */
    public $order;

    /**
     * @var Object
     */
    private $quote;

    public function __construct(
        \CheckoutCom\Magento2\Model\Api\Data\PaymentResponseFactory $paymentResponseFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService $orderStatusHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService $paymentErrorHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->paymentResponseFactory = $paymentResponseFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->utilities = $utilities;
        $this->vaultHandler = $vaultHandler;
        $this->request = $request;
    }

    public function executeApiV3(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface $paymentRequest
    ) {
        // Assign the customer and payment request to be accessible to the whole class
        $this->customer = $customer;
        $this->data = $paymentRequest;

        // Prepare the V3 object
        return $this->execute();
    }

    public function executeGuestApiV3(
        \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface $paymentRequest
    ) {
        // Assign the payment request to be accessible to the whole class
        $this->data = $paymentRequest;

        // Prepare the V3 object
        return $this->execute();
    }

    /**
     * Get an API handler instance and the request data.
     */
    private function execute()
    {
        // Get an API handler instance
        $this->api = $this->apiHandler->init(
            $this->storeManager->getStore()->getCode()
        );

        // Prepare the default response
        $this->result = [
            'success' => false,
            'order_id' => 0,
            'redirect_url' => '',
            'error_message' => []
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
            return __('The public key is invalid.');
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
     * Check if the request is valid.
     */
    private function isValidPublicKey()
    {
        return $this->config->isValidAuth('pk', 'Cko-Authorization');
    }

    /**
     * Process the payment request and handle the response.
     *
     * @return Array
     */
    private function processPayment()
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

                if ($is3ds) {
                    $this->result['redirect_url'] = $response->_links['redirect']['href'];
                }

                // Add the payment info to the order
                $order = $this->utilities->setPaymentData($order, $response);

                // Save the order
                $order->save();

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
            }

            // Update the order id
            $this->result['order_id'] = $order->getIncrementId();
        }

        return $this->result;
    }

    /**
     * Create an order.
     *
     * @param $methodId
     * @return Order
     */
    private function createOrder($methodId)
    {
        // Load the quote
        $this->quote = $this->loadQuote();
        $order = null;

        if ($this->quote) {
            // Create an order
            $order = $this->orderHandler
                ->setMethodId($methodId)
                ->handleOrder($this->quote);
        }
        return $order;
    }

    /**
     * Load the quote.
     */
    private function loadQuote()
    {
        // Convert masked quote ID hash to quote ID int
        if (preg_match("/([A-Za-z])\w+/", $this->data->getQuoteId())) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($this->data->getQuoteId(), 'masked_id');
            $this->data->setQuoteId($quoteIdMask->getQuoteId());
        }
        
        // Load the quote
        $quote = $this->quoteHandler->getQuote([
            'entity_id' => $this->data->getQuoteId()
        ]);

        // Handle a quote not found
        if (!$this->quoteHandler->isQuote($quote)) {
            $this->result['error_message'][] = __('No quote found with the provided ID');
            $quote = null;
        }

        return $quote;
    }
    
    /**
     * Get a payment response.
     *
     * @return Object
     */
    private function getPaymentResponse($order)
    {
        $sessionId = $this->request->getParam('cko-session-id');

        return ($sessionId && !empty($sessionId))
            ? $this->api->getPaymentDetails($sessionId)
            : $this->requestPayment($order);
    }

    /**
     * Request payment to API handler.
     *
     * @return Response
     */
    private function requestPayment($order)
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
            
            if ($this->data->getSaveCard() !== null
                && $this->data->getSaveCard() === true
                && $saveCardEnabled
                && isset($this->customer)
            ) {
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
        return $this->methodHandler
            ->get($this->data->getPaymentMethod())
            ->sendPaymentRequest(
                $payload,
                $order->getGrandTotal(),
                $order->getOrderCurrencyCode(),
                $order->getIncrementId(),
                $this->quote,
                true,
                $this->customer ? $this->customer->getId() : null
            );
    }

    private function hasValidFields()
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
            }
        } else {
            $this->result['error_message'][] = __('Payment method is missing from request body');
            $isValid = false;
        }

        // Check the quote id has been specified correctly
        if ($this->data->getQuoteId() !== null) {
            if (is_integer($this->data->getQuoteId()) && $this->data->getQuoteId() < 1) {
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
        if ($this->data->getPaymentMethod()== 'checkoutcom_vault' && isset($this->customer)) {
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
                if ($this->data->getCardCvv() == null || (int) $this->data->getCardCvv() == 0) {
                    $this->result['error_message'][] = __('CVV value is required');
                    $isValid = false;
                }
            } else if ($this->data->getCardCvv()) {
                $this->result['error_message'][] = __('CVV value is not required');
                $isValid = false;
            }
        } elseif ($this->data->getPaymentMethod()== 'checkoutcom_vault' && !isset($this->customer)) {
            $this->result['error_message'][] = __('Vault payment method is not available for guest checkouts.');
            $isValid = false;
        }
        
        return $isValid;
    }
}
