<?php

namespace CheckoutCom\Magento2\Controller\Apm;

use Checkout\CheckoutApi;
use Checkout\Models\Product;
use Checkout\Library\HttpHandler;
use Checkout\Models\Sources\Sepa;
use Checkout\Models\Sources\Klarna;

class DisplayKlarna extends \Magento\Framework\App\Action\Action {

	/**
     * @var Context
     */
    protected $context;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Address
     */
    protected $billingAddress;

    /**
     * Locale code.
     * @var string
     */
    protected $locale;

    /**
     * Display constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler
    ) {

        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->config = $config;

        // Try to load a quote
        $this->quoteHandler = $quoteHandler;
        $this->quote = $this->quoteHandler->getQuote();
        $this->billingAddress = $quoteHandler->getBillingAddress();

        $this->apiHandler = $apiHandler;
        $this->locale = str_replace('_', '-', $shopperHandler->getCustomerLocale());

    }

    /**
     * Handles the controller method.
     */
    public function execute() {

        $klarna = $this->getKlarna();

        return $this->jsonFactory->create()
                                 ->setData($klarna);

    }

    /**
     * Gets the klarna.
     *
     * @return     array  The klarna.
     */
    protected function getKlarna() {

        $response = array();
        $products = $this->getProducts($response);

        $klarna = new Klarna(strtolower($this->billingAddress->getCountry()),
                             $this->quote->getQuoteCurrencyCode(),
                             $this->locale,
                             $this->quote->getGrandTotal() *100 ,
                             $response['tax_amount'],
                             $products
                         );

        $source = $this->apiHandler->checkoutApi->sources()->add($klarna);

        if($source->isSuccessful()) {
            $response['source'] = $source->getValues();
            $response['billing'] = $this->billingAddress->toArray();
            $response['quote'] = $this->quote->toArray();
        } else {
            $response = array('source' => false);
        }

        return $response;

    }

    /**
     * Gets the products.
     *
     * @param      array   $response  The response
     *
     * @return     array  The products.
     */
    protected function getProducts(array &$response) {

        $response['tax_amount'] = 0;
        foreach ($this->quote->getAllVisibleItems() as $item) {

            $product = new Product();
            $product->name = $item->getName();
            $product->quantity = $item->getQty();
            $product->unit_price = $item->getPriceInclTax() *100;
            $product->tax_rate = $item->getTaxPercent() *100;
            $product->total_amount = $item->getRowTotalInclTax() *100;
            $product->total_tax_amount = $item->getTaxAmount() *100;

            $response['tax_amount']  += $product->total_tax_amount;
            $products []= $product;
            $response['products'] []= $product->getValues();

        }

        $this->getShipping($response, $products);

        return $products;

    }

    /**
     * Gets the products.
     *
     * @param      array   $response  The response
     *
     * @return     array  The products.
     */
    protected function getShipping(array &$response, array &$products) {

        $response['tax_amount'] = 0;
        $shipping = $this->quote->getShippingAddress();

        $product = new Product();
        $product->name = $shipping->getShippingDescription();
        $product->quantity = 1;
        $product->unit_price = $shipping->getShippingInclTax() *100;
        $product->tax_rate = $shipping->getTaxPercent() *100;
        $product->total_amount = $shipping->getShippingAmount() *100;
        $product->total_tax_amount = $shipping->getTaxAmount() *100;
        $product->type = 'shipping_fee';

        $response['tax_amount']  += $product->total_tax_amount;
        $products []= $product;
        $response['products'] []= $product->getValues();

    }

}
