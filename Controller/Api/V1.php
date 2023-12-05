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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Api;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class V1
 */
class V1 extends Action
{
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    private $jsonFactory;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
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
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $data field
     *
     * @var array $data
     */
    private $data;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;

    /**
     * Callback constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param QuoteHandlerService $quoteHandler
     * @param OrderHandlerService $orderHandler
     * @param MethodHandlerService $methodHandler
     * @param ApiHandlerService $apiHandler
     * @param Utilities $utilities
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Handles the controller method
     *
     * @return Json
     */
    public function execute(): Json
    {
        try {
            // Set the response parameters
            $success = false;
            $orderId = 0;
            $errorMessage = '';

            // Get the request parameters
            $this->data = json_decode($this->getRequest()->getContent());

            // Validate the request
            if ($this->isValidRequest()) {
                // Load the quote
                $quote = $this->loadQuote();
                $order = null;
                $reservedOrderId = null;

                if ($this->config->isPaymentWithOrderFirst()) {
                    // Create an order
                    $order = $this->orderHandler->setMethodId('checkoutcom_card_payment')->handleOrder($quote);
                }

                if ($this->config->isPaymentWithPaymentFirst()) {
                    // Reserved an order
                    /** @var string $reservedOrderId */
                    $reservedOrderId = $this->quoteHandler->getReference($quote);
                }



                // Process the payment
                if (($this->config->isPaymentWithPaymentFirst() && $this->quoteHandler->isQuote($quote) && $reservedOrderId !== null)
                    || ($this->config->isPaymentWithOrderFirst() && $this->orderHandler->isOrder($order))
                ) {
                    //Init values to request payment
                    $amount = (float)$this->config->isPaymentWithPaymentFirst() ? $quote->getGrandTotal() : $order->getGrandTotal();
                    $currency = (string)$this->config->isPaymentWithPaymentFirst() ? $quote->getQuoteCurrencyCode() : $order->getOrderCurrencyCode();
                    $reference = (string)$this->config->isPaymentWithPaymentFirst() ? $reservedOrderId : $order->getIncrementId();

                    // Get response and success
                    $response = $this->requestPayment($amount, $currency, $reference);

                    // Get the store code
                    $storeCode = $this->storeManager->getStore()->getCode();

                    // Process the response
                    $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
                    if ($api->isValidResponse($response)) {
                        // Create an order if processing is with payment first
                        $order = $order === null ? $this->orderHandler->setMethodId('checkoutcom_card_payment')->handleOrder($quote) : $order;

                        // Get the payment details
                        $paymentDetails = $api->getPaymentDetails($response->id);

                        // Add the payment info to the order
                        $order = $this->utilities->setPaymentData($order, $response);

                        // Save the order
                        $this->orderRepository->save($order);

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
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        } finally {
            // Return the json response
            return $this->jsonFactory->create()->setData([
                'success' => $success,
                'order_id' => $orderId,
                'error_message' => $errorMessage,
            ]);
        }
    }

    /**
     * Check if the request is valid
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function isValidRequest(): bool
    {
        return $this->config->isValidAuth('pk') && $this->dataIsValid();
    }

    /**
     * Check if the data is valid
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function dataIsValid(): bool
    {
        // Check the quote ID
        if ((!isset($this->data->quote_id) && !isset($this->data['quote_id'])) || (int)$this->data->quote_id == 0) {
            throw new LocalizedException(
                __('The quote ID is missing or invalid.')
            );
        }

        // Check the payment token
        if (!isset($this->data->payment_token) || empty($this->data->payment_token)) {
            throw new LocalizedException(
                __('The payment token is missing or invalid.')
            );
        }

        return true;
    }

    /**
     * Load the quote
     *
     * @return DataObject|CartInterface|Quote
     * @throws LocalizedException
     */
    protected function loadQuote()
    {
        if (!isset($this->data->quote_id)) {
            $this->data->quote_id = $this->data['quote_id'];
        }

        // Load the quote
        $quote = $this->quoteHandler->getQuote([
            'entity_id' => $this->data->quote_id,
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
     * Request payment to API handler
     *
     * @param float $amount
     * @param string $currencyCode
     * @param string $reference
     *
     * @return mixed
     */
    protected function requestPayment(float $amount, string $currencyCode, string $reference)
    {
        // Prepare the payment request payload
        $payload = [
            'cardToken' => $this->data->payment_token,
        ];

        if (isset($this->data->card_bin)) {
            $payload['cardBin'] = $this->data->card_bin;
        }

        // Send the charge request
        return $this->methodHandler->get('checkoutcom_card_payment')->sendPaymentRequest(
            $payload,
            $amount,
            $currencyCode,
            $reference
        );
    }
}
