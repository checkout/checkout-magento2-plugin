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

namespace CheckoutCom\Magento2\Controller\Api;

use CheckoutCom\Magento2\Model\Service\CardHandlerService;

/**
 * Class V2
 */
class V2 extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var QuoteIdMaskFactory
     */
    public $quoteIdMaskFactory;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var OrderStatusHandlerService
     */
    public $orderStatusHandler;

    /**
     * @var MethodHandlerService
     */
    public $methodHandler;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /*
     * @var CardHandlerService
     */
    public $cardHandler;

    /*
     * @var PaymentErrorHandlerService
     */
    public $paymentErrorHandler;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Object
     */
    public $data;

    /**
     * @var Array
     */
    public $result;

    /**
     * @var Object
     */
    public $api;

    /**
     * @var Object
     */
    public $order;

    /**
     * @var Object
     */
    public $quote;

    /**
     * Callback constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService $orderStatusHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService $paymentErrorHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->cardHandler = $cardHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->utilities = $utilities;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        // Prepare the V2 object
        $this->init();

        // Process the payment
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

        // Return the json response
        return $this->jsonFactory->create()->setData($this->result);
    }

    /**
     * Get an API handler instance and the request data.
     */
    public function init()
    {
        // Get the request parameters
        $this->data = json_decode($this->getRequest()->getContent());

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
    }

    /**
     * Process the payment request and handle the response.
     *
     * @return Array
     */
    public function processPayment()
    {
        $order = $this->createOrder();
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
                // Get the payment details
                $paymentDetails = $this->api->getPaymentDetails($response->id);

                // Add the payment info to the order
                $order = $this->utilities->setPaymentData($order, $response);

                // Save the order
                $order->save();

                // Update the result
                $this->result['success'] = $response->isSuccessful();
            } else {
                // Payment failed
                if (isset($response->response_code)) {
                    $this->result['error_message'][] = $this->paymentErrorHandler->getErrorMessage($response->response_code);
                }

                //  Token invalid/expired
                if (method_exists($response, 'getErrors')) {
                    $this->result['error_message'] = array_merge($this->result['error_message'], $response->getErrors());
                }
            }

            // Update the order id
            $this->result['order_id'] = $order->getIncrementId();
        }

        return $this->result;
    }

    /**
     * Request payment to API handler.
     *
     * @return Response
     */
    public function requestPayment($order)
    {
        // Prepare the payment request payload
        $payload = [
            'cardToken' => $this->data->payment_token
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
        return $this->methodHandler
        ->get('checkoutcom_card_payment')
        ->sendPaymentRequest(
            $payload,
            $order->getGrandTotal(),
            $order->getOrderCurrencyCode(),
            $order->getIncrementId(),
            $this->quote,
            true
        );
    }

    /**
     * Get a payment response.
     *
     * @return Object
     */
    public function getPaymentResponse($order)
    {
        $sessionId = $this->getRequest()->getParam('cko-session-id');
        return ($sessionId && !empty($sessionId))
        ? $this->api->getPaymentDetails($sessionId)
        : $this->requestPayment($order);
    }

    /**
     * Load the quote.
     */
    public function loadQuote()
    {
        // Get the quote id
        if (!isset($this->data->quote_id)) {
            $this->data->quote_id = $this->data['quote_id'];
        }

        // Convert masked quote ID hash to quote ID int
        if (preg_match("/([A-Za-z])\w+/", $this->data->quote_id)) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($this->data->quote_id, 'masked_id');
            $this->data->quote_id = $quoteIdMask->getQuoteId();
        }

        // Load the quote
        $quote = $this->quoteHandler->getQuote([
            'entity_id' => $this->data->quote_id
        ]);

        // Handle a quote not found
        if (!$this->quoteHandler->isQuote($quote)) {
            $this->result['error_message'][] = __('No quote found with the provided ID');
            $quote = null;
        }

        return $quote;
    }

    /**
     * Check if the request is valid.
     */
    public function isValidPublicKey()
    {
        return $this->config->isValidAuth('pk');
    }

    public function hasValidFields()
    {
        $isValid = true;

        if (isset($this->data->payment_token)) {
            if (gettype($this->data->payment_token) !== 'string') {
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
            if (gettype($this->data->quote_id) === 'integer' && $this->data->quote_id < 1) {
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
                if (gettype($this->data->success_url) !== 'string') {
                    $this->result['error_message'][] = __('Success URL provided is not a string');
                    $isValid = false;
                } elseif ($this->data->success_url == '') {
                    $this->result['error_message'][] = __('Success URL is empty string');
                    $isValid = false;
                }
            }

            if (isset($this->data->failure_url)) {
                if (gettype($this->data->failure_url) !== 'string') {
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
     * Create an order.
     *
     * @return Order
     */
    public function createOrder()
    {
        // Load the quote
        $this->quote = $this->loadQuote();
        $order = null;

        if ($this->quote) {
            // Create an order
            $order = $this->orderHandler
                ->setMethodId('checkoutcom_card_payment')
                ->handleOrder($this->quote);
        }
        return $order;
    }
}
