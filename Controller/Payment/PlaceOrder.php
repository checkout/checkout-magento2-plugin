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
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use Magento\Store\Model\ScopeInterface;

/**
 * Class PlaceOrder
 */
class PlaceOrder extends \Magento\Framework\App\Action\Action
{
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

    /**
     * @var PaymentErrorHandler
     */
    public $paymentErrorHandler;

    /**
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var array
     */
    public $data;

    /**
     * @var Quote
     */
    public $quote;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService $orderStatusHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService $paymentErrorHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \Magento\Checkout\Model\Session $session
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->session = $session;
    }

    /**
     * Main controller function.
     *
     * @return JSON
     */
    public function execute()
    {
        try {
            // Prepare some parameters
            $url = '';
            $message = '';
            $debugMessage = '';
            $responseCode = '';
            $success = false;
            $log = true;

            // Try to load a quote
            $this->quote = $this->quoteHandler->getQuote();

            // Set some required properties
            $this->data = $this->getRequest()->getParams();

            if (!$this->isEmptyCardToken($this->data)) {
                // Process the request
                if ($this->getRequest()->isAjax() && $this->quote) {
                    // Create an order
                    $order = $this->orderHandler
                        ->setMethodId($this->data['methodId'])
                        ->handleOrder($this->quote);

                    // Process the payment
                    if ($this->orderHandler->isOrder($order)) {
                        $log = false;
                        // Get the debug config value
                        $debug = $this->scopeConfig->getValue(
                            'settings/checkoutcom_configuration/debug',
                            ScopeInterface::SCOPE_STORE
                        );

                        // Get the gateway response config value
                        $gatewayResponses = $this->scopeConfig->getValue(
                            'settings/checkoutcom_configuration/gateway_responses',
                            ScopeInterface::SCOPE_STORE
                        );

                        // Get response and success
                        $response = $this->requestPayment($order);

                        // Logging
                        $this->logger->display($response);

                        // Get the store code
                        $storeCode = $this->storeManager->getStore()->getCode();

                        // Process the response
                        $api = $this->apiHandler->init($storeCode);
                        if ($api->isValidResponse($response)) {
                            // Add the payment info to the order
                            $order = $this->utilities->setPaymentData($order, $response, $this->data);

                            // check for redirection
                            if (isset($response->_links['redirect']['href'])) {
                                // set order status to pending payment
                                $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                            }

                            // Save the order
                            $order->save();

                            // Update the response parameters
                            $success = $response->isSuccessful();
                            $url = $response->getRedirection();
                        } else {

                            // Payment failed
                            if (isset($response->response_code)) {
                                $message = $this->paymentErrorHandler->getErrorMessage($response->response_code);
                                if ($debug && $gatewayResponses) {
                                    $responseCode = $response->response_code;
                                }
                            } else {
                                $message = __('The transaction could not be processed.');
                                if ($debug && $gatewayResponses) {
                                    $debugMessage = json_encode($response);
                                }
                            }

                            // Restore the quote
                            $this->session->restoreQuote();

                            // Handle order on failed payment
                            $this->orderStatusHandler->handleFailedPayment($order);
                        }
                    } else {
                        // Payment failed
                        $message = __('The order could not be processed.');
                    }
                } else {
                    // Payment failed
                    $message = __('The request is invalid or there was no quote found.');
                }
            } else {
                // No token found
                $message = __("Please enter valid card details.");
            }
        } catch (\Exception $e) {
            $success = false;
            $message = __($e->getMessage());
            $this->logger->write($message);
        } finally {
            if ($log) {
                $this->logger->write($message);
            }

            return $this->jsonFactory->create()->setData([
                'success' => $success,
                'message' => $message,
                'responseCode' => $responseCode,
                'debugMessage' => $debugMessage,
                'url' => $url
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
        // Get the method id
        $methodId = $order->getPayment()
        ->getMethodInstance()
        ->getCode();

        // Send the charge request
        return $this->methodHandler
        ->get($methodId)
        ->sendPaymentRequest(
            $this->data,
            $order->getGrandTotal(),
            $order->getOrderCurrencyCode(),
            $order->getIncrementId()
        );
    }

    public function isEmptyCardToken($paymentData)
    {
        if ($paymentData['methodId'] == "checkoutcom_card_payment") {
            if (!isset($paymentData['cardToken'])
                || empty($paymentData['cardToken'])
                || $paymentData['cardToken'] == ""
            ) {
                return true;
            }
        }
        return false;
    }
}
