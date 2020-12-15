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

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Customer\Api\Data\GroupInterface;

/**
 * Class QuoteHandlerService.
 */
class QuoteHandlerService
{
    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var CookieManagerInterface
     */
    public $cookieManager;

    /**
     * @var QuoteFactory
     */
    public $quoteFactory;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var ShopperHandlerService
     */
    public $shopperHandler;

    /**
     * QuoteHandlerService constructor
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->quoteFactory = $quoteFactory;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->config = $config;
        $this->shopperHandler = $shopperHandler;
    }

    /**
     * Find a quote
     */
    public function getQuote($fields = [])
    {
        if (!empty($fields)) {
            // Get the quote factory
            $quoteFactory = $this->quoteFactory
                ->create()
                ->getCollection();

            // Add search filters
            foreach ($fields as $key => $value) {
                $quoteFactory->addFieldToFilter(
                    $key,
                    $value
                );
            }

            // Return the first result found
            return $quoteFactory
                ->setPageSize(1)
                ->getLastItem();
        } else {
            // Try to find the quote in session
            return $this->checkoutSession->getQuote();
        }
    }

    /**
     * Create a new quote
     */
    public function createQuote($currency = null, $customer = null)
    {
        // Create the quote instance
        $quote = $this->quoteFactory->create();
        $quote->setStore($this->storeManager->getStore());

        // Set the currency
        if ($currency) {
            $quote->setCurrency($currency);
        } else {
            $quote->setCurrency();
        }

        // Set the quote customer
        if ($customer) {
            $quote->assignCustomer($customer);
        } else {
            $quote->assignCustomer($this->shopperHandler->getCustomerData());
        }

        return $quote;
    }

    /**
     * Checks if a quote exists and is valid
     */
    public function isQuote($quote)
    {
        return $quote
        && is_object($quote)
        && method_exists($quote, 'getId')
        && $quote->getId() > 0;
    }

    /**
     * Get the order increment id from a quote
     */
    public function getReference($quote)
    {
        return $quote->reserveOrderId()
            ->save()
            ->getReservedOrderId();
    }

    /**
     * Prepares a quote for order placement
     */
    public function prepareQuote($methodId, $quote = null)
    {
        // Find quote and perform tasks
        $quote = $quote ? $quote : $this->getQuote();
        if ($this->isQuote($quote)) {
            // Prepare the inventory
            $quote->setInventoryProcessed(false);

            // Check for guest user quote
            if (!$this->customerSession->isLoggedIn() && $quote->getCustomerId() == null) {
                $quote = $this->prepareGuestQuote($quote);
            }

            // Set the payment information
            $payment = $quote->getPayment();
            $payment->setMethod($methodId);

            return $quote;
        }

        return null;
    }

    /**
     * Sets the email for guest users
     */
    public function prepareGuestQuote($quote, $email = null)
    {
        // Retrieve the user email
        $guestEmail = ($email) ? $email : $this->findEmail($quote);

        // Set the quote as guest
        $quote->setCustomerId(null)
            ->setCustomerEmail($guestEmail)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

        // Delete the cookie
        $this->cookieManager->deleteCookie(
            $this->config->getValue('email_cookie_name')
        );

        return $quote;
    }

    /**
     * Find a customer email
     */
    public function findEmail($quote)
    {
        // Get an array of possible values
        $emails = [
            $quote->getCustomerEmail(),
            $quote->getBillingAddress()->getEmail(),
            $this->cookieManager->getCookie(
                $this->config->getValue('email_cookie_name')
            )
        ];

        // Return the first available value
        foreach ($emails as $email) {
            if ($email && !empty($email)) {
                return $email;
            }
        }

        return null;
    }

    /**
     * Gets an array of quote parameters
     */
    public function getQuoteData()
    {
        return [
            'value' => $this->getQuoteValue(),
            'currency' => $this->getQuoteCurrency()
        ];
    }

    /**
     * Gets a quote currency
     */
    public function getQuoteCurrency($quote = null)
    {
        $quote = ($quote) ? $quote : $this->getQuote();
        $quoteCurrencyCode = $quote->getQuoteCurrencyCode();
        $storeCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        return ($quoteCurrencyCode) ? $quoteCurrencyCode : $storeCurrencyCode;
    }

    /**
     * Convert a quote amount to integer value for the gateway request.
     */
    public function amountToGateway($amount, $quote)
    {
        // Get the quote currency
        $currency = $this->getQuoteCurrency($quote);

        // Get the x1 currency calculation mapping
        $currenciesX1 = explode(
            ',',
            $this->config->getValue('currencies_x1')
        );

        // Get the x1000 currency calculation mapping
        $currenciesX1000 = explode(
            ',',
            $this->config->getValue('currencies_x1000')
        );

        // Prepare the amount
        if (in_array($currency, $currenciesX1)) {
            return $amount;
        } elseif (in_array($currency, $currenciesX1000)) {
            return $amount*1000;
        } else {
            return $amount*100;
        }
    }

    /**
     * Gets a quote value
     */
    public function getQuoteValue()
    {
        return $this->getQuote()->collectTotals()->getGrandTotal();
    }

    /**
     * Saves the quote. This is needed
     */
    public function saveQuote()
    {
        $this->cartRepository->save($this->getQuote());
    }

    /**
     * Add product items to a quote
     */
    public function addItems($quote, $data)
    {
        $items = $this->buildProductData($data);
        foreach ($items as $item) {
            if (isset($item['product_id']) && (int) $item['product_id'] > 0) {
                // Load the product
                $product = $this->productRepository->getById($item['product_id']);

                // Get the quantity
                $quantity = isset($item['qty']) && (int) $item['qty'] > 0
                ? $item['qty'] : 1;

                // Add the item
                if (!empty($item['super_attribute'])) {
                    $buyRequest = new \Magento\Framework\DataObject($item);
                    $quote->addProduct($product, $buyRequest);
                } else {
                    $quote->addProduct($product, $quantity);
                }
            }
        }

        return $quote;
    }

    /**
     * Creates a formatted array with the purchased product data.
     *
     * @return array
     */
    public function buildProductData($data)
    {
        // Prepare the base array
        $output =[
            'product_id' => $data['product'],
            'qty' => $data['qty']
        ];

        // Add product variations
        if (isset($data['super_attribute']) && !empty($data['super_attribute'])) {
            $output['super_attribute'] = $data['super_attribute'];
        }

        return [$output];
    }

    /* Gets the billing address.
     *
     * @return     Address  The billing address.
     */
    public function getBillingAddress()
    {
        return $this->getQuote()->getBillingAddress();
    }

    /* Gets quote data for a payment request.
     *
     * @return array
     */
    public function getQuoteRequestData($quote)
    {
        return [
            'quote_id' => $quote->getId(),
            'store_id' => $quote->getStoreId(),
            'customer_email' => $quote->getCustomerEmail()
        ];
    }

    /**
     * Prepares the quote filters.
     *
     * @param array $paymentDetails
     * @param string $reservedIncrementId
     *
     * @return array
     */
    public function prepareQuoteFilters($paymentDetails, $reservedIncrementId)
    {
        // Prepare the filters array
        $filters = ['increment_id' => $reservedIncrementId];

        // Retrieve the quote metadata
        $quoteData = isset($paymentDetails->metadata['quoteData'])
        && !empty($paymentDetails->metadata['quoteData'])
        ? json_decode($paymentDetails->metadata['quoteData'], true)
        : [];

        return array_merge($filters, $quoteData);
    }
}
