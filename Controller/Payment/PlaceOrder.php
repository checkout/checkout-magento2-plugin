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
    protected $quoteHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var MethodHandlerService
     */
    protected $methodHandler;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var String
     */
    protected $cardToken;

    /**
     * @var Quote
     */
    protected $quote;

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

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Set some required properties
        $this->data = $this->getRequest()->getParams();
        $this->methodId = $this->data['methodId'];
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
            // Process the request
            if ($this->getRequest()->isAjax() && $this->quote) {
                // Get response and success
                $response = $this->requestPayment();

                // Logging
                $this->logger->display($response);

                // Check success
                if ($this->apiHandler->isValidResponse($response)) {
                    $success = $response->isSuccessful();
                    $url = $response->getRedirection();
                    if ($this->canPlaceOrder($response)) {
                        $this->placeOrder($response);
                    }
                    else {
                        // Order placement not possible
                        $message = __('The order cannot not be placed.');
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
    protected function requestPayment()
    {
        try {
            // Send the charge request
            return $this->methodHandler
                ->get($this->methodId)
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
    protected function canPlaceOrder($response)
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
    protected function placeOrder($response = null)
    {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($this->quote);

            // Create an order
            $order = $this->orderHandler
                ->setMethodId($this->methodId)
                ->handleOrder($response, $reservedIncrementId);

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
     * Cancels a payment.
     *
     * @param array $response The response
     *
     * @return void
     */
    protected function cancelPayment($response)
    {
        try {
            // refund or void accordingly
            if ($this->config->needsAutoCapture($this->methodId)) {
                //refund
                $this->apiHandler->checkoutApi->payments()->refund(new Refund($response->getId()));
            } else {
                //void
                $this->apiHandler->checkoutApi->payments()->void(new Voids($response->getId()));
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }
}
