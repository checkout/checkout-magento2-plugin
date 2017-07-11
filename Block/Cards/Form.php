<?php

namespace CheckoutCom\Magento2\Block\Cards;

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
