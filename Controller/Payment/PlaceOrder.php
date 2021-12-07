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

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PlaceOrder
 */
class PlaceOrder extends Action
{
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    public $storeManager;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    public $quoteHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    public $orderHandler;
    /**
     * $orderStatusHandler field
     *
     * @var OrderStatusHandlerService $orderStatusHandler
     */
    public $orderStatusHandler;
    /**
     * $methodHandler field
     *
     * @var MethodHandlerService $methodHandler
     */
    public $methodHandler;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    public $apiHandler;
    /**
     * $paymentErrorHandler field
     *
     * @var PaymentErrorHandler $paymentErrorHandler
     */
    public $paymentErrorHandler;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    public $jsonFactory;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    public $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    public $logger;
    /**
     * $session field
     *
     * @var Session $session
     */
    protected $session;
    /**
     * $data field
     *
     * @var array $data
     */
    public $data;
    /**
     * $quote field
     *
     * @var Quote $quote
     */
    public $quote;
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    public $scopeConfig;

    /**
     * PlaceOrder constructor
     *
     * @param Context                    $context
     * @param StoreManagerInterface      $storeManager
     * @param JsonFactory                $jsonFactory
     * @param ScopeConfigInterface        $scopeConfig
     * @param QuoteHandlerService        $quoteHandler
     * @param OrderHandlerService        $orderHandler
     * @param OrderStatusHandlerService  $orderStatusHandler
     * @param MethodHandlerService       $methodHandler
     * @param ApiHandlerService          $apiHandler
     * @param PaymentErrorHandlerService $paymentErrorHandler
     * @param Utilities                  $utilities
     * @param Logger                     $logger
     * @param Session                    $session
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory,
        ScopeConfigInterface $scopeConfig,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        PaymentErrorHandlerService $paymentErrorHandler,
        Utilities $utilities,
        Logger $logger,
        Session $session
    ) {
        parent::__construct($context);

        $this->storeManager        = $storeManager;
        $this->jsonFactory         = $jsonFactory;
        $this->scopeConfig         = $scopeConfig;
        $this->quoteHandler        = $quoteHandler;
        $this->orderHandler        = $orderHandler;
        $this->orderStatusHandler  = $orderStatusHandler;
        $this->methodHandler       = $methodHandler;
        $this->apiHandler          = $apiHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->utilities           = $utilities;
        $this->logger              = $logger;
        $this->session             = $session;
    }

    /**
     * Main controller function
     *
     * @return Json
     */
    public function execute()
    {
        try {
            // Prepare some parameters
            $url          = '';
            $message      = '';
            $debugMessage = '';
            $responseCode = '';
            $success      = false;
            $log          = true;

            // Try to load a quote
            $this->quote = $this->quoteHandler->getQuote();

            // Set some required properties
            $this->data = $this->getRequest()->getParams();

            if (!$this->isEmptyCardToken($this->data)) {
                // Process the request
                if ($this->getRequest()->isAjax() && $this->quote) {
                    // Create an order
                    $order = $this->orderHandler->setMethodId($this->data['methodId'])->handleOrder($this->quote);

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
                            $url     = $response->getRedirection();
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
                'success'      => $success,
                'message'      => $message,
                'responseCode' => $responseCode,
                'debugMessage' => $debugMessage,
                'url'          => $url,
            ]);
        }
    }

    /**
     * Request payment to API handler
     *
     * @param $order
     *
     * @return mixed
     */
    public function requestPayment($order)
    {
        // Get the method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Send the charge request
        return $this->methodHandler->get($methodId)->sendPaymentRequest(
                $this->data,
                $order->getGrandTotal(),
                $order->getOrderCurrencyCode(),
                $order->getIncrementId()
            );
    }

    /**
     * Description isEmptyCardToken function
     *
     * @param $paymentData
     *
     * @return bool
     */
    public function isEmptyCardToken($paymentData)
    {
        if ($paymentData['methodId'] == "checkoutcom_card_payment") {
            if (!isset($paymentData['cardToken']) || empty($paymentData['cardToken']) || $paymentData['cardToken'] == "") {
                return true;
            }
        }

        return false;
    }
}
