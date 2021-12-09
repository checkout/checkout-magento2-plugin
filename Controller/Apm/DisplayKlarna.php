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

namespace CheckoutCom\Magento2\Controller\Apm;

use Checkout\CheckoutApi;
use \Checkout\Models\Product;
use \Checkout\Models\Sources\Klarna;
use \Checkout\Library\Exceptions\CheckoutHttpException;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\ShopperHandlerService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DisplayKlarna
 */
class DisplayKlarna extends Action
{
    /**
     * $context field
     *
     * @var Context $context
     */
    public $context;
    /**
     * $storeManager
     *
     * @var StoreManagerInterface $storeManager
     */
    public $storeManager;
    /**
     * $jsonFactory
     *
     * @var JsonFactory $jsonFactory
     */
    public $jsonFactory;
    /**
     * $apiHandler field
     *
     * @var CheckoutApi $apiHandler
     */
    public $apiHandler;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    public $quoteHandler;
    /**
     * $shopperHandler field
     *
     * @var ShopperHandlerService $shopperHandler
     */
    public $shopperHandler;
    /**
     * $utilities
     *
     * @var Utilities $utilities
     */
    public $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    public $logger;
    /**
     * $quote field
     *
     * @var Quote $quote
     */
    public $quote;
    /**
     * $billingAddress field
     *
     * @var Address $billingAddress
     */
    public $billingAddress;
    /**
     * Locale code.
     *
     * @var string $locale
     */
    public $locale;

    /**
     * DisplayKlarna constructor
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory           $jsonFactory
     * @param ApiHandlerService     $apiHandler
     * @param QuoteHandlerService   $quoteHandler
     * @param ShopperHandlerService $shopperHandler
     * @param Utilities             $utilities
     * @param Logger                $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory,
        ApiHandlerService $apiHandler,
        QuoteHandlerService $quoteHandler,
        ShopperHandlerService $shopperHandler,
        Utilities $utilities,
        Logger $logger
    ) {
        parent::__construct($context);

        $this->storeManager   = $storeManager;
        $this->jsonFactory    = $jsonFactory;
        $this->apiHandler     = $apiHandler;
        $this->quoteHandler   = $quoteHandler;
        $this->shopperHandler = $shopperHandler;
        $this->utilities      = $utilities;
        $this->logger         = $logger;
    }

    /**
     * Handles the controller method
     *
     * @return Json
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        // Get the request data
        $quoteId = $this->getRequest()->getParam('quote_id');
        $storeId = $this->getRequest()->getParam('store_id');

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote([
            'entity_id' => $quoteId,
            'store_id'  => $storeId,
        ]);

        $this->billingAddress = $this->quoteHandler->getBillingAddress();
        $this->locale         = str_replace('_', '-', $this->shopperHandler->getCustomerLocale());

        // Get Klarna
        $klarna = $this->getKlarna();

        return $this->jsonFactory->create()->setData($klarna);
    }

    /**
     * Gets the Klarna response.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getKlarna()
    {
        try {
            // Prepare the output array
            $response = ['source' => false];

            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            $products = $this->getProducts($response);
            $klarna   = new Klarna(
                strtolower($this->billingAddress->getCountry()),
                $this->quote->getQuoteCurrencyCode(),
                $this->locale,
                $this->quoteHandler->amountToGateway(
                    $this->utilities->formatDecimals(
                        $this->quote->getGrandTotal()
                    ),
                    $this->quote
                ),
                $response['tax_amount'],
                $products
            );

            $source = $api->checkoutApi->sources()->add($klarna);
            if ($source->isSuccessful()) {
                // Prepare the response
                $response['source']  = $source->getValues();
                $response['billing'] = $this->billingAddress->toArray();
                $response['quote']   = $this->quote->toArray();

                // Handle missing email for guest checkout
                if ($response['billing']['email'] === null || empty($response['billing']['email'])) {
                    $response['billing']['email'] = $this->quoteHandler->findEmail($this->quote);
                }
            }

            return $response;
        } catch (CheckoutHttpException $e) {
            $this->logger->write($e->getBody());
        }
    }

    /**
     * Gets the products.
     *
     * @param array $response The response
     *
     * @return array  The products.
     */
    public function getProducts(array &$response)
    {
        $products               = [];
        $response['tax_amount'] = 0;
        foreach ($this->quote->getAllVisibleItems() as $item) {
            $product                   = new Product();
            $product->name             = $item->getName();
            $product->quantity         = $item->getQty();
            $product->unit_price       = $item->getPriceInclTax() * 100;
            $product->tax_rate         = $item->getTaxPercent() * 100;
            $product->total_amount     = $item->getRowTotalInclTax() * 100;
            $product->total_tax_amount = $item->getTaxAmount() * 100;

            $response['tax_amount'] += $product->total_tax_amount;
            $products[]             = $product;
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
     *
     * @return void
     */
    public function getShipping(array &$response, array &$products)
    {
        $shipping = $this->quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
            $product                   = new Product();
            $product->name             = $shipping->getShippingDescription();
            $product->quantity         = 1;
            $product->unit_price       = $shipping->getShippingInclTax() * 100;
            $product->tax_rate         = $shipping->getTaxPercent() * 100;
            $product->total_amount     = $shipping->getShippingAmount() * 100;
            $product->total_tax_amount = $shipping->getTaxAmount() * 100;
            $product->type             = 'shipping_fee';

            $response['tax_amount']  += $product->total_tax_amount;
            $products []             = $product;
            $response['products'] [] = $product->getValues();
        }
    }
}
