<?php

namespace CheckoutCom\Magento2\Model\Service;

class QuoteHandlerService
{

    protected $checkoutSession;
    protected $config;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->checkoutSession = $checkoutSession;
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
     * Get a quote value.
     *
     * @return float
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

    public function getQuoteCurrency() {
        try {            
            return $this->getQuote()->getQuoteCurrencyCode() 
            ?? $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch (\Exception $e) {
            return false;
        }
    }


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