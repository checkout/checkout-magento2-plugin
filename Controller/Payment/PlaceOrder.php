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

namespace CheckoutCom\Magento2\Controller\Payment;

use Checkout\Models\Payments\Payment;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
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
    private $storeManager;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
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
     * @var PaymentErrorHandler $paymentErrorHandler
     */
    private $paymentErrorHandler;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    private $jsonFactory;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;
    /**
     * $session field
     *
     * @var Session $session
     */
    private $session;
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;

    /**
     * PlaceOrder constructor
     *
     * @param Context                    $context
     * @param StoreManagerInterface      $storeManager
     * @param JsonFactory                $jsonFactory
     * @param ScopeConfigInterface       $scopeConfig
     * @param QuoteHandlerService        $quoteHandler
     * @param OrderHandlerService        $orderHandler
     * @param OrderStatusHandlerService  $orderStatusHandler
     * @param MethodHandlerService       $methodHandler
     * @param ApiHandlerService          $apiHandler
     * @param PaymentErrorHandlerService $paymentErrorHandler
     * @param Utilities                  $utilities
     * @param Logger                     $logger
     * @param Session                    $session
     * @param OrderRepositoryInterface   $orderRepository
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
        Session $session,
        OrderRepositoryInterface $orderRepository
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
        $this->orderRepository     = $orderRepository;
    }

    /**
     * Main controller function
     *
     * @return Json
     */
    public function execute(): Json
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
            $quote = $this->quoteHandler->getQuote();

            // Set some required properties
            $data = $this->getRequest()->getParams();

            if (!$this->isEmptyCardToken($data)) {
                // Process the request
                if ($this->getRequest()->isAjax() && $quote) {
                    // Create an order
                    $order = $this->orderHandler->setMethodId($data['methodId'])->handleOrder($quote);

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
                        $response = $this->requestPayment($order, $data);

                        // Logging
                        $this->logger->display($response);

                        // Get the store code
                        $storeCode = $this->storeManager->getStore()->getCode();

                        // Process the response
                        $api = $this->apiHandler->init($storeCode);
                        if ($api->isValidResponse($response)) {
                            // Add the payment info to the order
                            $order = $this->utilities->setPaymentData($order, $response, $data);

                            // check for redirection
                            if (isset($response->_links['redirect']['href'])) {
                                // set order status to pending payment
                                $order->setStatus(Order::STATE_PENDING_PAYMENT);
                            }

                            // Save the order
                            $this->orderRepository->save($order);

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
        } catch (Exception $e) {
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
     * @param $data
     *
     * @return Payment|null
     */
    protected function requestPayment($order, $data): ?Payment
    {
        // Get the method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Send the charge request
        return $this->methodHandler->get($methodId)->sendPaymentRequest(
            $data,
            $order->getGrandTotal(),
            $order->getOrderCurrencyCode(),
            $order->getIncrementId()
        );
    }

    /**
     * Description isEmptyCardToken function
     *
     * @param string[] $paymentData
     *
     * @return bool
     */
    public function isEmptyCardToken(array $paymentData): bool
    {
        if ($paymentData['methodId'] === "checkoutcom_card_payment") {
            if (!isset($paymentData['cardToken']) || empty($paymentData['cardToken']) || $paymentData['cardToken'] == "") {
                return true;
            }
        }

        return false;
    }
}
