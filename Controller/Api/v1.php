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

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class V1
 */
class V1 extends \Magento\Framework\App\Action\Action
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
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        try {
            // Set the response parameters
            $success = false;
            $orderId = 0;
            $errorMessage = '';

            // Get the request parameters
            $this->data = $this->getRequest()->getParams();

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
                    // Get response and success
                    $response = $this->requestPayment($order);

                    // Process the response
                    $api = $this->apiHandler->init($storeCode);
                    if ($api->isValidResponse($response)) {
                        // Get the payment details
                        $paymentDetails = $api->getPaymentDetails($response->id);
            
                        // Add the payment info to the order
                        $order = $this->utilities->setPaymentData($order, $response);
            
                        // Save the order
                        $order->save();

                        // Update the response parameters
                        $success = $response->isSuccessful();
                        $orderId = $order->getId();
                    } else {
                        $errorMessage = __('The payment request was declined by the gateway.');
                    }
                } else {
                    $errorMessage = __('The order could not be created.');
                }
            } else {
                $errorMessage = __('The request is invalid.');
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        } finally {
            // Return the json response
            return $this->jsonFactory->create()->setData([
                'success' => $success,
                'order_id' => $orderId,
                'error_message' => $errorMessage
            ]);
        }
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
            'cardToken' => $this->data['payment_token']
        ];
        
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
        // Load the quote
        $quote = $this->quoteHandler->getQuote([
            'entity_id' => $this->data['quote_id']
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
        return $this->config->isValidAuth('pk')
        && $this->dataIsValid();
    }

    /**
     * Check if the data is valid.
     */
    public function dataIsValid()
    {
        // Check the quote ID
        if (!isset($this->data['quote_id']) || (int) $this->data['quote_id'] == 0) {
            throw new LocalizedException(
                __('The quote ID is missing or invalid.')
            );
        }

        // Check the payment token
        if (!isset($this->data['payment_token']) || empty($this->data['payment_token'])) {
            throw new LocalizedException(
                __('The payment token is missing or invalid.')
            );
        }

        return true;
    }
}
