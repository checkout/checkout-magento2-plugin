<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class Embedded extends Template {

	protected $gatewayConfig;

    /**
     * Block constructor.
     */
    public function __construct(Context $context, GatewayConfig $gatewayConfig) {
        parent::__construct($context);
        $this->gatewayConfig = $gatewayConfig;
    }
              
    public function getEmbeddedCss() {
        return $this->gatewayConfig->getEmbeddedCss();
    }

    public function hasCustomCss() {

    	// Get the custom CSS file
        $css_file = $this->_scopeConfig->getValue('payment/checkout_com/checkout_com_base_settings/css_file');
		$custom_css_file = $this->_scopeConfig->getValue('payment/checkout_com/checkout_com_base_settings/custom_css');

		// Determine if there is a custom CSS file
		return (bool) (isset($custom_css_file) && !empty($custom_css_file) && $css_file == 'custom');
    }

    public function getEmbeddedUrl() {
        return $this->gatewayConfig->getEmbeddedUrl();
    }
}
