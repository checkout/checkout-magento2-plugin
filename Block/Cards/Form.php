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
     * Form constructor.
     * @param GatewayConfig $gatewayConfig
     * @param CcConfig $ccConfig
     * @param Session $session
     * @param Context $context
     * @param array $data
     */
    public function __construct(GatewayConfig $gatewayConfig, CcConfig $ccConfig, Session $session, Context $context, array $data = []) {
        parent::__construct($context, $data);

        $this->gatewayConfig    = $gatewayConfig;
        $this->ccConfig         = $ccConfig;
        $this->session          = $session;
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
     * Returns the HTML img element with CVV image.
     *
     * @return string
     */
    public function getCvvImgHtml() {
        return '<img src=' . $this->ccConfig->getCvvImageUrl() . ' />';
    }

    /**
     * Returns the HTML option list with CC years.
     *
     * @return string
     */
    public function getYearsForSelect() {
        $options = [];

        foreach($this->ccConfig->getCcYears() as $year) {
            $options[] = sprintf('<option value="%s">%s</option>', $year, $year);
        }
        
        return implode('', $options);
    }

    /**
     * Returns the HTML option list with CC months.
     *
     * @return string
     */
    public function getMonthsForSelect() {
        $options = [];

        foreach($this->ccConfig->getCcMonths() as $key => $month) {
            $key = str_pad($key, 2, '0', STR_PAD_LEFT);

            $options[]  = sprintf('<option value="%s">%s</option>', $key, $month);
        }

        return implode('', $options);
    }

}
