<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Customer\Api\Data\GroupInterface;
class QuoteHandlerService
{

    const EMAIL_COOKIE_NAME = 'email';

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
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->cookieManager = $cookieManager;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * Find a quote
     */
    public function getQuote($reservedIncrementId = null) {
        try {
            if ($reservedIncrementId) {
                return $this->quoteFactory
                    ->create()->getCollection()
                    ->addFieldToFilter('reserved_order_id', $reservedIncrementId)
                    ->getFirstItem();
            }

            return $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            return false;
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
     * Sets the email for guest users
     */
    public function prepareGuestQuote($quote, $email = null)
    {
        // Retrieve the user email
        $guestEmail = ($email) ? $email : $this->findCustomerEmail($quote);
        // Set the quote as guest
        $quote->setCustomerId(null)
            ->setCustomerEmail($guestEmail)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        // Delete the cookie
        $this->cookieManager->deleteCookie(self::EMAIL_COOKIE_NAME);
        // Return the quote
        return $quote;
    }

    /**
     * Finds a customer email
     */
    public function findCustomerEmail($quote)
    {
        return $quote->getCustomerEmail()
        ?? $quote->getBillingAddress()->getEmail()
        ?? $this->cookieManager->getCookie(self::EMAIL_COOKIE_NAME);
    }

}