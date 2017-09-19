<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Block\Adminhtml\Cards;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use Magento\Customer\Model\Session;
use Magento\Payment\Model\CcConfig;
 
class Form extends Template {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     *
     * @var CcConfig
     */
    protected $ccConfig;
    
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Form constructor.
     * @param GatewayConfig $gatewayConfig
     * @param CcConfig $ccConfig
     * @param Session $session
     * @param Context $context
     * @param array $data
     */
    public function __construct(GatewayConfig $gatewayConfig, CcConfig $ccConfig, Session $session, Context $context, array $data = []) {

        $this->gatewayConfig    = $gatewayConfig;
        $this->ccConfig         = $ccConfig;
        $this->session          = $session;
        $this->storeManager     = $context->getStoreManager();
  
        parent::__construct($context, $data);
    }

    public function getSaveCardCheckAmount() {

        // Get the configured amount
        $amount = $this->_scopeConfig->getValue('payment/checkout_com/save_card_check_amount');

        return $amount;
    }

    public function getSaveCardCheckCurrency() {

        // Get the configured currency
        $currency = $this->_scopeConfig->getValue('payment/checkout_com/save_card_check_currency');

        return $currency;
    }

    /**
     * Returns the customer instance from the session.
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer() {
        return $this->session->getCustomer();
    }

    /**
     * Returns the gateway config.
     *
     * @return GatewayConfig
     */
    public function getGatewayConfig() {
        return $this->gatewayConfig;
    }

    /**
     * Returns the url for the form.
     *
     * @return string
     */
    public function getFormActionUrl() {
        return $this->_urlBuilder->getRouteUrl('checkout_com/cards/store');
    }

    /**
     * Returns the current user currency preference.
     *
     * @return string
     */
    public function getCustomerCurrency() {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

}
