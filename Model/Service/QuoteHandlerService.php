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
     * @var Config
     */
    protected $config;

    /**
     * @var ShopperHandlerService
     */
    protected $shopperHandlerService;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->cookieManager = $cookieManager;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
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
     * Checks if a quote exists and is valid
     */
    public function isQuote($quote)
    {
        return $quote
        && is_object($quote)
        && method_exists($quote, 'getId')
        && $quote->getId() > 0
    }

    /**
     * Sets the email for guest users
     */
    public function prepareGuestQuote($quote, $email = null)
    {
        // Retrieve the user email
        $guestEmail = ($email) ? $email : $this->shopperHandler->findEmail();

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
}