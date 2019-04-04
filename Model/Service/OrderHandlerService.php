<?php

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use \Magento\Checkout\Model\Cart;

class OrderHandlerService
{

    protected $config;
    protected $cart;

    /**
     * @param Context $context
     */
    public function __construct(
    	Config $config,
    	Cart $cart
    )
    {

    	$this->cart = $cart;


print_r($this->getCurrency());die();



        $this->config = $config;

    }

    /**
     * Public Getters
     */

    /**
     * Gets the currency.
     */
    public function getCurrency() {

    	return $this->findQuote()->getBaseCurrencyCode();

    }

    /**
     * Gets the amount.
     */
    public function getAmount() {

    	return 1000;

    }


    /**
     * Find a quote
     */
    protected function findQuote($reservedIncrementId = null) {

        // if ($reservedIncrementId) {
        //     return $this->quoteFactory
		      //           ->create()->getCollection()
		      //           ->addFieldToFilter('reserved_order_id', $reservedIncrementId)
		      //           ->getFirstItem();
        // }
        try {
            return $this->cart->getQuote();
        } catch (\Exception $e) {
            // $this->watchdog->logError($e);
            return false;
        }
    }

}