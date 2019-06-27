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

namespace CheckoutCom\Magento2\Controller\Apm;

use Checkout\CheckoutApi;
use Checkout\Models\Product;
use Checkout\Models\Sources\Klarna;

/**
 * Class DisplayKlarna
 */
class DisplayKlarna extends \Magento\Framework\App\Action\Action
{

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
     *
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
    public function execute()
    {
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
     * Gets the Klarna response.
     *
     * @return array
     */
    protected function getKlarna()
    {
        // Prepare the output array
        $response = ['source' => false];

        try {
            $products = $this->getProducts($response);
            $klarna = new Klarna(
                strtolower($this->billingAddress->getCountry()),
                $this->quote->getQuoteCurrencyCode(),
                $this->locale,
                $this->quote->getGrandTotal() *100,
                $response['tax_amount'],
                $products
            );

            $source = $this->apiHandler->checkoutApi->sources()->add($klarna);

            if ($source->isSuccessful()) {
                $response['source'] = $source->getValues();
                $response['billing'] = $this->billingAddress->toArray();
                $response['quote'] = $this->quote->toArray();
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getBody());
        } finally {
            return $response;
        }
    }

    /**
     * Gets the products.
     *
     * @param array $response The response
     *
     * @return array  The products.
     */
    protected function getProducts(array &$response)
    {

        $products = [];
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

        // Get the shipping
        $this->getShipping($response, $products);

        // Return the products
        return $products;
    }

    /**
     * Gets the shipping.
     *
     * @param array $response The response
     * @param array $products The products.
     * @return void
     */
    protected function getShipping(array &$response, array &$products)
    {

        $shipping = $this->quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
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
}
