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
     * @var ShopperHandlerService
     */
    protected $shopperHandler;

    /**
     * @var Logger
     */
    protected $logger;
    
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
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {

        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->shopperHandler = $shopperHandler;
        $this->logger = $logger;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();
        $this->billingAddress = $quoteHandler->getBillingAddress();
        $this->locale = str_replace('_', '-', $shopperHandler->getCustomerLocale());
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        try {
            $klarna = $this->getKlarna();

            return $this->jsonFactory->create()
                ->setData($klarna);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());

            return $this->jsonFactory->create()
            ->setData([]);        
        }
    }

    /**
     * Gets the klarna.
     *
     * @return     array  The klarna.
     */
    protected function getKlarna() {
        // Prepare the output array
        $response = [];

        try {
            $products = $this->getProducts($response);

            $klarna = new Klarna(strtolower($this->billingAddress->getCountry()),
                                $this->quote->getQuoteCurrencyCode(),
                                $this->locale,
                                $this->quote->getGrandTotal() *100,
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

        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $response;
        }
    }

    /**
     * Gets the products.
     *
     * @param      array   $response  The response
     *
     * @return     array  The products.
     */
    protected function getProducts(array &$response) {
        try {
            $response['tax_amount'] = 0;
            foreach ($this->quote->getAllVisibleItems() as $item) {
                $product = new Product();
                $product->name = $item->getName();
                $product->quantity = $item->getQty();
                $product->unit_price = $item->getPriceInclTax() *100;
                $product->tax_rate = $item->getTaxPercent() *100;
                $product->total_amount = $item->getRowTotalInclTax() *100;
                $product->total_tax_amount = $item->getTaxAmount() *100;

                $response['tax_amount'] += $product->total_tax_amount;
                $products[]= $product;
                $response['products'][] = $product->getValues();
            }

            return $products;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());

            return null;
        }
    }
}
