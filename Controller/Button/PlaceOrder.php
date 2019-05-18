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
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\InstantPurchase\Model\QuoteManagement\ShippingConfiguration $shippingConfiguration,
        \Magento\Customer\Model\Address $addressManager,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    ) {
        parent::__construct($context);

        $this->quoteManagement = $quoteManagement;
        $this->productModel = $productModel;
        $this->shippingConfiguration = $shippingConfiguration;
        $this->addressManager = $addressManager;
        $this->quoteHandler = $quoteHandler;
    }

    public function execute()
    {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/data.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($this->getRequest()->getParams(), 1));

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
                [
                    'id' => $productId,
                    'quantity' => $quantity
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

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/quote.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($quote->getData(), 1));

        // Create the order
        $order = $this->quoteManagement->submit($quote);

        exit();
    }

}
