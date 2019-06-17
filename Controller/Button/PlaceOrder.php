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

class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

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
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\InstantPurchase\Model\QuoteManagement\ShippingConfiguration $shippingConfiguration,
        \Magento\Customer\Model\Address $addressManager,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->quoteManagement = $quoteManagement;
        $this->productModel = $productModel;
        $this->shippingConfiguration = $shippingConfiguration;
        $this->addressManager = $addressManager;
        $this->quoteHandler = $quoteHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->logger = $logger;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Set some required properties
        $this->data = $this->getRequest()->getParams();

        // Prepare the public hash
        $this->data['publicHash'] = $this->data['instant_purchase_payment_token'];
    }

    public function execute()
    {
        // Prepare a default error message
        $message = _('An error occurred and the order could not be created.');

        // Try to place the order
        try {
            // Create the quote
            $quote = $this->quoteHandler->createQuote();
            $quote = $this->quoteHandler->addItems(
                $quote,
                [
                    [
                        'product_id' => $this->data['product'],
                        'qty' => $this->data['qty'],
                        'super_attribute' => $this->data['super_attribute']
                    ]
                ]
            );

            // Set the billing address
            $billingAddress = $this->addressManager->load($this->data['instant_purchase_billing_address']);
            $quote->getBillingAddress()->addData($billingAddress->getData());

            // Set the shipping address and method
            $shippingAddress = $this->addressManager->load($this->data['instant_purchase_shipping_address']);
            $quote->getShippingAddress()
                ->addData($shippingAddress->getData())
                ->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('flatrate_flatrate');

            // Set payment
            $quote->setPaymentMethod('checkoutcom_vault');
            $quote->setInventoryProcessed(false);
            $quote->save();
            $quote->getPayment()->importData(
                ['method' => 'checkoutcom_vault']
            );

            // Save the quote
            $quote->collectTotals()->save();

            // Process the response
            $response = $this->methodHandler
                ->get('checkoutcom_vault')
                ->sendPaymentRequest(
                    $this->data,
                    $this->quote->getGrandTotal(),
                    $this->quote->getQuoteCurrencyCode(),
                    $this->quoteHandler->getReference($this->quote)
                );

            // Process a successful response
            if ($this->apiHandler->isValidResponse($response)) {
                // Create the order
                $order = $this->quoteManagement->submit($quote);

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
     * Creates response with the operation status message.
     */
    private function createResponse(string $message, bool $successMessage)
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
}
