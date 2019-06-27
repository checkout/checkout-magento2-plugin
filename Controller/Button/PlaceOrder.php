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

/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Button;

use \Checkout\Models\Payments\Refund;
use \Checkout\Models\Payments\Voids;

/**
 * Class PlaceOrder
 */
class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Product
     */
    protected $productModel;

    /**
     * @var ShippingConfiguration
     */
    private $shippingConfiguration;

    /**
     * @var Address
     */
    protected $addressManager;

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
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ShippingSelector
     */
    protected $shippingSelector;

    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\InstantPurchase\Model\QuoteManagement\ShippingConfiguration $shippingConfiguration,
        \Magento\Customer\Model\Address $addressManager,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \CheckoutCom\Magento2\Model\InstantPurchase\ShippingSelector $shippingSelector
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->productModel = $productModel;
        $this->shippingConfiguration = $shippingConfiguration;
        $this->addressManager = $addressManager;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->shippingSelector = $shippingSelector;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Set some required properties
        $this->data = $this->getRequest()->getParams();

        // Prepare the public hash
        $this->data['publicHash'] = $this->data['instant_purchase_payment_token'];

        // Set some required properties
        $this->methodId = 'checkoutcom_vault';
    }

    /**
     * Handles the controller method.
     *
     * @return array
     */
    public function execute()
    {
        // Try to place the order
        try {
            // Prepare a default error message
            $message = __('An error occurred and the order could not be created.');

            // Create the quote
            $quote = $this->quoteHandler->createQuote();
            $quote = $this->quoteHandler->addItems(
                $quote,
                $this->buildProductData()
            );

            // Set the billing address
            $billingAddress = $this->addressManager->load($this->data['instant_purchase_billing_address']);
            $quote->getBillingAddress()->addData($billingAddress->getData());

            // Get the shipping address
            $shippingAddress = $this->addressManager->load($this->data['instant_purchase_shipping_address']);

            // Prepare the quote
            $quote->getShippingAddress()->addData($shippingAddress->getData());

            // Set the shipping method
            $shippingMethodCode = $this->shippingSelector->getShippingMethod($quote->getShippingAddress());
            $quote->getShippingAddress()->setShippingMethod($shippingMethodCode)
                ->setCollectShippingRates(true)
                ->collectShippingRates();

            // Set payment
            $quote->setPaymentMethod($this->methodId);
            $quote->setInventoryProcessed(false);
            $quote->save();
            $quote->getPayment()->importData(
                ['method' => $this->methodId]
            );

            // Save the quote
            $quote->collectTotals()->save();

            // Process the response
            $response = $this->methodHandler
                ->get($this->methodId)
                ->sendPaymentRequest(
                    $this->data,
                    $quote->getGrandTotal(),
                    $quote->getQuoteCurrencyCode(),
                    $this->quoteHandler->getReference($quote)
                );

            // Process a successful response
            if ($this->apiHandler->isValidResponse($response)) {
                // Create the order
                $order = $this->placeOrder($quote, $response);

                // Prepare the user response
                $message = __(
                    'Your order number %1 has been created successfully.',
                    $order->getIncrementId()
                );
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return $this->createResponse(
                $message,
                false
            );
        } finally {
            return $this->createResponse($message, true);
        }
    }

    /**
     * Handles the order placing process.
     *
     * @param array $response The response
     *
     * @return void
     */
    protected function placeOrder($quote, $response = null)
    {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($quote);

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
     * Creates a formatted array with the purchased product data.
     *
     * @return array
     */
    protected function buildProductData()
    {
        try {
            // Prepare the base array
            $output =[
                'product_id' => $this->data['product'],
                'qty' => $this->data['qty']
            ];

            // Add product variations
            if (isset($this->data['super_attribute']) && !empty($this->data['super_attribute'])) {
                $output['super_attribute'] = $this->data['super_attribute'];
            }

            return [$output];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return [];
        }
    }

    /**
     * Creates response with the operation status message.
     *
     * @return array
     */
    protected function createResponse(string $message, bool $successMessage)
    {
        // Prepare the result
        $result = $this->jsonFactory->create()->setData(
            ['response' => $message]
        );

        // Prepare the response message
        if ($successMessage) {
            $this->messageManager->addSuccessMessage($message);
        } else {
            $this->messageManager->addErrorMessage($message);
        }

        return $result;
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
