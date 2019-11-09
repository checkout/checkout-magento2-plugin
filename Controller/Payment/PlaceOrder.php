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

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Set some required properties
        $this->data = $this->getRequest()->getParams();

        // Process the request
        if ($this->getRequest()->isAjax() && $this->quote) {
            // Create an order
            $order = $this->orderHandler
                ->setMethodId($this->data['methodId'])
                ->handleOrder();

            // Process the payment
            if ($this->orderHandler->isOrder($order)) { 
                // Get response and success
                $response = $this->requestPayment();

                // Logging
                $this->logger->display($response);

                // Get the payment details
                $api = $this->apiHandler->init();
                if ($api->isValidResponse($response)) {
                    // Get the payment details
                    $paymentDetails = $api->getPaymentDetails($response->id);
        
                    // Add the payment info to the order
                    $order = $this->utilities->setPaymentData($order, $response);
        
                    // Save the order
                    $order->save();

                    // Update the response parameters
                    $success = $response->isSuccessful();
                    $url = $response->getRedirection();
                }
            }
            else {
                // Payment failed
                $message = __('The transaction could not be processed.');
            }
        }

        // Return the json response
        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'message' => $message,
            'url' => $url
        ]);
    }

    /**
     * Request payment to API handler.
     *
     * @return Response
     */
    public function requestPayment()
    {
        // Send the charge request
        return $this->methodHandler
        ->get($this->data['methodId'])
        ->sendPaymentRequest(
            $this->data,
            $this->quote->getGrandTotal(),
            $this->quote->getQuoteCurrencyCode(),
            $this->quoteHandler->getReference($this->quote)
        );
    }
}
