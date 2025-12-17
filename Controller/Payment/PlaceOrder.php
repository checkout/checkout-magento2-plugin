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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Payment;

use CheckoutCom\Magento2\Gateway\Config\Config;
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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PlaceOrder
 */
class PlaceOrder extends Action
{
    private StoreManagerInterface $storeManager;
    private JsonFactory $jsonFactory;
    private ScopeConfigInterface $scopeConfig;
    private QuoteHandlerService $quoteHandler;
    private OrderHandlerService $orderHandler;
    private OrderStatusHandlerService $orderStatusHandler;
    private MethodHandlerService $methodHandler;
    private ApiHandlerService $apiHandler;
    private PaymentErrorHandlerService $paymentErrorHandler;
    private Utilities $utilities;
    private Logger $logger;
    private Session $session;
    private OrderRepositoryInterface $orderRepository;
    protected JsonSerializer $json;
    private Config $config;

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
        OrderRepositoryInterface $orderRepository,
        JsonSerializer $json,
        Config $config
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
        $this->orderRepository = $orderRepository;
        $this->json = $json;
        $this->config = $config;
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
            $url = '';
            $message = '';
            $debugMessage = '';
            $responseCode = '';
            $success = false;
            $log = true;
            // Try to load a quote
            $quote = $this->quoteHandler->getQuote();

            // Set some required properties
            $data = $this->getRequest()->getParams();
            if (isset($data['methodId']) && !$this->isEmptyCardToken($data)) {
                // Process the request
                if ($this->getRequest()->isAjax() && $quote) {
                    //Create order before payment
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

                        //Init values to request payment
                        $amount = (float)$order->getGrandTotal();
                        $currency = (string)$order->getOrderCurrencyCode();
                        $reference = (string)$order->getIncrementId();

                        // Get response and success
                        $response = $this->requestPayment($quote, $data, $amount, $currency, $reference);

                        // Logging
                        $this->logger->display($response);

                        // Get the store code
                        $storeCode = $this->storeManager->getStore()->getCode();

                        // Process the response
                        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

                        $isValidResponse = $api->isValidResponse($response);
                        $responseCode = isset($response['response_code']) ? $response['response_code'] : '';

                        if ($isValidResponse && $this->isAuthorized($responseCode)) {
                            // Add the payment info to the order
                            $order = $this->utilities->setPaymentData($order, $response, $data);

                            // set order status to pending payment
                            $order->setStatus(Order::STATE_PENDING_PAYMENT);

                            // check for redirection
                            if (isset($response['_links']['redirect']['href'])) {
                                $url = $response['_links']['redirect']['href'];
                            }

                            // Save the order
                            $this->orderRepository->save($order);
                            // Update the response parameters
                            $success = $isValidResponse;
                        } else {
                            // Payment failed
                            if (isset($response['response_code'])) {
                                $message = $this->paymentErrorHandler->getErrorMessage($response['response_code']);
                                if ($debug && $gatewayResponses) {
                                    $responseCode = $response['response_code'];
                                }
                            } else {
                                $message = __('The transaction could not be processed.');
                                if ($debug && $gatewayResponses) {
                                    $debugMessage = $this->json->serialize($response);
                                }
                            }

                            // Restore the quote
                            $this->session->restoreQuote();

                            // Handle order on failed payment
                            if ($this->config->isPaymentWithOrderFirst()) {
                                $this->orderStatusHandler->handleFailedPayment($order);
                            }

                            // Delete the order if payment is first
                            $this->orderHandler->deleteOrder($order);
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
            $this->logger->write($e->getMessage());
        } finally {
            if ($log) {
                $this->logger->write($message);
            }

            return $this->jsonFactory->create()->setData([
                'success' => $success,
                'message' => $message ?: __('An error has occurred, please select another payment method'),
                'responseCode' => $responseCode,
                'debugMessage' => $debugMessage,
                'url' => $url,
            ]);
        }
    }

    /**
     * @param array $paymentData
     *
     * @return bool
     */
    public function isEmptyCardToken(array $paymentData): bool
    {
        if ($paymentData['methodId'] === 'checkoutcom_card_payment') {
            if (empty($paymentData['cardToken'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Request payment to API handler
     *
     * @param CartInterface $quote
     * @param array $data
     * @param float $amount
     * @param string $currencyCode
     * @param string $reference
     *
     * @return array|null
     */
    protected function requestPayment(CartInterface $quote, array $data, float $amount, string $currencyCode, string $reference): ?array
    {
        if ($quote->getPayment()->getMethod() === null) {
            $paymentMethod = $data['methodId'];
            $quote->setPaymentMethod($paymentMethod); //payment method
            $quote->getPayment()->importData(['method' => $paymentMethod]);
        }

        // Get the method id
        $methodId = $quote->getPayment()->getMethodInstance()->getCode();

        // Send the charge request
        return $this->methodHandler->get($methodId)->sendPaymentRequest(
            $data,
            $amount,
            $currencyCode,
            $reference
        );
    }

    /**
     * Check if response code is successful
     *
     * @param string $responseCode
     *
     * @return bool
     */
    protected function isAuthorized(string $responseCode): bool
    {
        return empty($responseCode) || mb_substr($responseCode, 0, 2) === PaymentErrorHandlerService::TRANSACTION_SUCCESS_DIGITS;
    }
}
