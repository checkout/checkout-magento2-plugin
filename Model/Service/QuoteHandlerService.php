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
     * @var Logger
     */
    protected $logger;

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
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
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
        $this->logger = $logger;
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Create a new quote
     */
    public function createQuote()
    {
        try {
            // Create the quote instance
            $quote = $this->quoteFactory->create();
            $quote->setStore($this->storeManager->getStore());
            $quote->setCurrency();

            // Set the quote customer
            $quote->assignCustomer($this->shopperHandler->getCustomerData());

            return $quote;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Checks if a quote exists and is valid
     */
    public function isQuote($quote)
    {
        try {
            return $quote
            && is_object($quote)
            && method_exists($quote, 'getId')
            && $quote->getId() > 0;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
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
            $this->logger->write($e->getMessage());
            return '';
        }
    }

    /**
     * Prepares a quote for order placement
     */
    public function prepareQuote($fields = [], $methodId, $isWebhook = false)
    {
        try {
            // Find quote and perform tasks
            $quote = $this->getQuote($fields);
            if ($this->isQuote($quote)) {
                // Prepare the inventory
                $quote->setInventoryProcessed(false);

                // Check for guest user quote
                if (!$this->customerSession->isLoggedIn() && !$isWebhook) {
                    $quote = $this->prepareGuestQuote($quote);
                }

                // Set the payment information
                $payment = $quote->getPayment();
                $payment->setMethod($methodId);
                $payment->save();

                return $quote;
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Sets the email for guest users
     */
    public function prepareGuestQuote($quote, $email = null)
    {
        try {
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

        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $quote;
        }
    }

    /**
     * Find a customer email
     */
    public function findEmail($quote)
    {
        try {
            return $quote->getCustomerEmail()
            ?? $quote->getBillingAddress()->getEmail()
            ?? $this->cookieManager->getCookie(
                $this->config->getValue('email_cookie_name')
            );
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
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
            $this->logger->write($e->getMessage());
            return null;
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
            $this->logger->write($e->getMessage());
            return null;
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
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Add product items to a quote
     */
    public function addItems($quote, $items) {
        try {
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $quote;
        }
    }

    /* Gets the billing address.
     *
     * @return     Address  The billing address.
     */
    public function getBillingAddress() {
        try {
            return $this->getQuote()->getBillingAddress();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}