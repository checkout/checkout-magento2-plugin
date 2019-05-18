<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Customer\Api\Data\GroupInterface;

class QuoteHandlerService
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ShopperHandlerService
     */
    protected $shopperHandler;

    /**
     * QuoteHandlerService constructor
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->config = $config;
        $this->shopperHandler = $shopperHandler;
    }

    /**
     * Find a quote
     */
    public function getQuote($fields = []) {
        try {
            if (count($fields) > 0) {
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
                return $quoteFactory->getFirstItem();
            }
            else {
                // Try to find the quote in session
                return $this->checkoutSession->getQuote();
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a new quote
     */
    public function createQuote()
    {
        // Create the quote instance
        $quote = $this->quoteFactory->create();
        $quote->setStore($this->storeManager->getStore());
        $quote->setCurrency();
        
        // Set the quote customer
        $quote->assignCustomer($this->shopperHandler->getCustomerData());

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
        try {
            return $quote->reserveOrderId()
                ->save()
                ->getReservedOrderId();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prepares a quote for order placement
     */
    public function prepareQuote($fields = [], $methodId)
    {
        // Find quote and perform tasks
        $quote = $this->getQuote($fields);
        if ($this->isQuote($quote)) {
            // Prepare the inventory
            $quote->setInventoryProcessed(false);

            // Check for guest user quote
            if ($this->customerSession->isLoggedIn() === false) {
                $quote = $this->prepareGuestQuote($quote);
            }

            // Set the payment information
            $payment = $quote->getPayment();
            $payment->setMethod($methodId);
            $payment->save();

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

        // Return the quote
        return $quote;
    }

    /**
     * Find a customer email
     */
    public function findEmail($quote)
    {
        return $quote->getCustomerEmail()
        ?? $quote->getBillingAddress()->getEmail()
        ?? $this->cookieManager->getCookie(
            $this->config->getValue('email_cookie_name')
        );
    }

    /**
     * Gets an array of quote parameters
     */
    public function getQuoteData() {
        try {
            return [
                'value' => $this->getQuoteValue(),
                'currency' => $this->getQuoteCurrency()
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Gets a quote currency
     */
    public function getQuoteCurrency() {
        try {            
            return $this->getQuote()->getQuoteCurrencyCode() 
            ?? $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Gets a quote value
     */
    public function getQuoteValue() {
        try {            
            return $this->getQuote()
            ->collectTotals()
            ->save()
            ->getGrandTotal();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add product items to a quote
     */
    public function addItems($quote, $items) {
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
                }
                else {
                    $quote->addProduct($product, $quantity);
                }
            }
        }

        // Return the quote
        return $quote;
    }
}