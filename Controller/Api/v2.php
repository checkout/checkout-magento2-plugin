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
     * @var OrderHandlerService
     */
    public $orderHandler;

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

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Array
     */
    public $data;

    /**
     * Callback constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->cardHandler = $cardHandler;
        $this->utilities = $utilities;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        // Prepare the default response
        $result = [
            'success' => false,
            'order_id' => 0,
            'redirect_url' => '',
            'error_message' => __('The payment request was declined by the gateway.')
        ];

        // Get the request parameters
        $this->data = json_decode($this->getRequest()->getContent());

        // Validate the request
        if ($this->isValidRequest()) {
            // Load the quote
            $quote = $this->loadQuote();

            // Create an order
            $order = $this->orderHandler
                ->setMethodId('checkoutcom_card_payment')
                ->handleOrder($quote);

            // Process the payment
            if ($this->orderHandler->isOrder($order)) {
                $result = $this->processPayment($order, $result);
            } else {
                $result['error_message'] = __('The order could not be created.');
            }
        } else {
            $result['error_message'] = __('The request is invalid.');
        }

        // Return the json response
        return $this->jsonFactory->create()->setData($result);
    }

    /**
     * Process the payment request and handle the response.
     *
     * @return Array
     */
    public function processPayment($order, $result)
    {
        // Send the payment request and get the response
        $response = $this->requestPayment($order);

        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Get an API handler instance
        $api = $this->apiHandler->init($storeCode);

        // Process the payment response
        $is3ds = property_exists($response, '_links')
        && isset($response->_links['redirect'])
        && isset($response->_links['redirect']['href']);
        if ($is3ds) {
            $result['success'] = true;
            $result['redirect_url'] = $response->_links['redirect']['href'];
            $result['error_message'] = '';
        }
        else if ($api->isValidResponse($response)) {
            // Get the payment details
            $paymentDetails = $api->getPaymentDetails($response->id);

            // Add the payment info to the order
            $order = $this->utilities->setPaymentData($order, $response);

            // Save the order
            $order->save();

            // Update the result
            $result['success'] = $response->isSuccessful();
            $result['order_id'] = $order->getId();
            $result['error_message'] = '';
        }

        // Return the result
        return $result;
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

        if (isset($this->data->card_bin)) {
            $payload['cardBin'] = $this->data->card_bin;
        }

        // Send the charge request
        return $this->methodHandler
        ->get('checkoutcom_card_payment')
        ->sendPaymentRequest(
            $payload,
            $order->getGrandTotal(),
            $order->getOrderCurrencyCode(),
            $order->getIncrementId()
        );
    }

    /**
     * Load the quote.
     */
    public function loadQuote()
    {
        if (!isset($this->data->quote_id)) {
            $this->data->quote_id = $this->data['quote_id'];
        }

        // Load the quote
        $quote = $this->quoteHandler->getQuote([
            'entity_id' => $this->data->quote_id
        ]);

        // Handle a quote not found
        if (!$this->quoteHandler->isQuote($quote)) {
            throw new LocalizedException(
                __('No quote was found with the provided ID.')
            );
        }

        return $quote;
    }

    /**
     * Check if the request is valid.
     */
    public function isValidRequest()
    {
        return $this->config->isValidAuth('pk');
    }
}
