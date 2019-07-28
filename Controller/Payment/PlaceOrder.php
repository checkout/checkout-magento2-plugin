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

namespace CheckoutCom\Magento2\Controller\Payment;

use \Checkout\Models\Payments\Refund;
use \Checkout\Models\Payments\Voids;

/**
 * Class PlaceOrder
 */
class PlaceOrder extends \Magento\Framework\App\Action\Action
{
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
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var String
     */
    public $methodId;

    /**
     * @var array
     */
    public $data;

    /**
     * @var String
     */
    public $cardToken;

    /**
     * @var Quote
     */
    public $quote;

    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->checkoutSession = $checkoutSession;
        $this->utilities = $utilities;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Main controller function.
     *
     * @return JSON
     */
    public function execute()
    {
        // Prepare some parameters
        $url = '';
        $message = '';
        $success = false;

        try {
            // Initialize the API handler
            $api = $this->apiHandler->init();

            // Try to load a quote
            $this->quote = $this->quoteHandler->getQuote();

            // Set some required properties
            $this->data = $this->getRequest()->getParams();

            // Process the request
            if ($this->getRequest()->isAjax() && $this->quote) {
                // Get response and success
                $response = $this->requestPayment();

                // Logging
                $this->logger->display($response);

                // Check success
                if ($api->isValidResponse($response)) {
                    $success = $response->isSuccessful();
                    $url = $response->getRedirection();
                    if ($this->canPlaceOrder($response)) {
                        $this->placeOrder($response);
                    }
                } else {
                    // Payment failed
                    $message = __('The transaction could not be processed.');
                }
            } else {
                $message = __('The request is invalid.');
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            $message = __($e->getMessage());
        } finally {
            return $this->jsonFactory->create()->setData(
                [
                    'success' => $success,
                    'message' => $message,
                    'url' => $url
                ]
            );
        }
    }

    /**
     * Request payment to API handler.
     *
     * @return Response
     */
    public function requestPayment()
    {
        try {
            // Send the charge request
            return $this->methodHandler
                ->get($this->data['methodId'])
                ->sendPaymentRequest(
                    $this->data,
                    $this->quote->getGrandTotal(),
                    $this->quote->getQuoteCurrencyCode(),
                    $this->quoteHandler->getReference($this->quote)
                );
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Checks if an order can be placed.
     *
     * @param array $response The response
     *
     * @return boolean
     */
    public function canPlaceOrder($response)
    {
        return !$response->isPending() || $this->data['source'] === 'sepa' || $this->data['source'] === 'fawry';
    }

    /**
     * Handles the order placing process.
     *
     * @param array $response The response
     *
     * @return void
     */
    public function placeOrder($response = null)
    {
        try {
            // Initialize the API handler
            $api = $this->apiHandler->init();

            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($this->quote);

            // Get the payment details
            $paymentDetails = $api->getPaymentDetails($response->id);

            // Prepare the quote filters
            $filters = $this->prepareQuoteFilters(
                $paymentDetails,
                $reservedIncrementId
            );

            // Create an order
            $order = $this->orderHandler
                ->setMethodId($this->data['methodId'])
                ->handleOrder($response, $filters);

            // Add the payment info to the order
            $order = $this->utilities
                ->setPaymentData($order, $response);

            // Save the order
            $order->save();

            // Check if the order is valid
            if (!$this->orderHandler->isOrder($order)) {
                $this->cancelPayment($response);
                return null;
            }

            return $order;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Prepares the quote filters.
     *
     * @param array $paymentDetails
     * @param string $reservedIncrementId
     *
     * @return array
     */
    public function prepareQuoteFilters($paymentDetails, $reservedIncrementId)
    {
        // Prepare the filters array
        $filters = ['increment_id' => $reservedIncrementId];

        // Retrieve the quote metadata
        $quoteData = isset($paymentDetails->metadata['quoteData'])
        && !empty($paymentDetails->metadata['quoteData'])
        ? json_decode($paymentDetails->metadata['quoteData'], true)
        : [];

        return array_merge($filters, $quoteData);
    }

    /**
     * Cancels a payment.
     *
     * @param array $response The response
     *
     * @return void
     */
    public function cancelPayment($response)
    {
        try {
            // Initialize the API handler
            $api = $this->apiHandler->init();

            // Refund or void accordingly
            if ($this->config->needsAutoCapture($this->data['methodId'])) {
                // Refund
                $api->checkoutApi->payments()->refund(new Refund($response->getId()));
            } else {
                // Void
                $api->checkoutApi->payments()->void(new Voids($response->getId()));
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }
}
