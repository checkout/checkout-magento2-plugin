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

use Magento\Checkout\Model\Type\Onepage;
class PlaceOrder extends \Magento\Framework\App\Action\Action
{
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
     * @var CustomerData
     */
    private $customerData;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;
    
    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\InstantPurchase\Model\QuoteManagement\ShippingConfiguration $shippingConfiguration,
        \Magento\Customer\Model\Address $addressManager,
        \CheckoutCom\Magento2\Model\InstantPurchase\CustomerData $customerData,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    ) {
        parent::__construct($context);

        $this->quoteManagement = $quoteManagement;
        $this->productModel = $productModel;
        $this->shippingConfiguration = $shippingConfiguration;
        $this->addressManager = $addressManager;
        $this->customerData = $customerData;
        $this->quoteHandler = $quoteHandler;
    }

    public function execute()
    {
        // Get the request parameters
        $productId = (int) $this->getRequest()->getParam('product');
        $quantity = (int) $this->getRequest()->getParam('qty');
        $billingId = (int) $this->getRequest()->getParam('instant_purchase_billing_address');
        $shippingId = (int) $this->getRequest()->getParam('instant_purchase_shipping_address');
        
        // Create the quote
        $quote = $this->quoteHandler->createQuote();
        $quote = $this->quoteHandler->addItems(
            $quote, 
            [
                'id' => $productId,
                'quantity' => $quantity
            ]
        );
        
        // Set the billing address
        $billingAddress = $this->addressManager->load($billingId);
        $quote->getBillingAddress()->addData($billingAddress->getData());

        // Set the shipping address
        $shippingAddress = $this->addressManager->load($shippingId);
        $quote->getShippingAddress()->addData($shippingAddress->getData());
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        // Set the shipping method
        $quote = $this->shippingConfiguration->configureShippingMethod(
            $quote,
            $this->customerData->instantPurchaseOption->getShippingMethod()
        );

        // Inventory
        $quote->setInventoryProcessed(false);

        // Set payment
        $payment = $quote->getPayment();
        $payment->setMethod('checkoutcom_vault');
        $quote->save();

        // Only registered users can order
        $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);

        // Set sales order payment
        $quote->getPayment()->importData(['method' => 'checkoutcom_vault']);

        // Save the quote
        $quote->collectTotals()->save();

        // Create the order
        $order = $this->quoteManagement->submit($quote);

        exit();
    }

}
