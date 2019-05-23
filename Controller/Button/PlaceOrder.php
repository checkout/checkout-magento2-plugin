<?php
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
     * @var UrlInterface
     */
    protected $urlInterface;

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
     * @var Utilities
     */
    protected $utilities;

    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\InstantPurchase\Model\QuoteManagement\ShippingConfiguration $shippingConfiguration,
        \Magento\Customer\Model\Address $addressManager,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        parent::__construct($context);

        $this->urlInterface = $urlInterface;
        $this->jsonFactory = $jsonFactory;
        $this->quoteManagement = $quoteManagement;
        $this->productModel = $productModel;
        $this->shippingConfiguration = $shippingConfiguration;
        $this->addressManager = $addressManager;
        $this->quoteHandler = $quoteHandler;
        $this->methodHandler = $methodHandler;
        $this->utilities = $utilities;
    }

    public function execute()
    {
        // Get the request parameters
        $productId = (int) $this->getRequest()->getParam('product');
        $quantity = (int) $this->getRequest()->getParam('qty');
        $attributes = $this->getRequest()->getParam('super_attribute');
        $billingId = (int) $this->getRequest()->getParam('instant_purchase_billing_address');
        $shippingId = (int) $this->getRequest()->getParam('instant_purchase_shipping_address');
        $publicHash = $this->getRequest()->getParam('instant_purchase_payment_token');

        try {
            // Create the quote
            $quote = $this->quoteHandler->createQuote();
            $quote = $this->quoteHandler->addItems(
                $quote,
                [
                    [
                        'product_id' => $productId,
                        'qty' => $quantity,
                        'super_attribute' => $attributes
                    ]
                ]
            );
        
            // Set the billing address
            $billingAddress = $this->addressManager->load($billingId);
            $quote->getBillingAddress()->addData($billingAddress->getData());

            // Set the shipping address and method
            $shippingAddress = $this->addressManager->load($shippingId);
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
                if ($response && $success = $response->isSuccessful()) {
                    // Create the order
                    $order = $this->quoteManagement->submit($quote);

                    // Create the order details URL
                    $url = $this->urlInterface->getUrl(
                        'sales/order/view/order_id/' . $order->getId()
                    );

                    // Prepare the user response
                    $message = __(
                        'Your order number %1 has been created successfully.',
                        $this->utilities->createLink(
                            $url,
                            $order->getIncrementId()
                        )
                    );
                }
        } catch (\Exception $e) {
            return $this->createResponse(
                $e->getMessage(),
                false
            );
        }

        return $this->createResponse($message, true);
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
