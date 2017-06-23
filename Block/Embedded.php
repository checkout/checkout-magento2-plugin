<?php

namespace CheckoutCom\Magento2\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class Embedded extends Template {

	protected $gatewayConfig;

    /**
     * Block constructor.
     */
    public function __construct(Context $context, array $data = [], GatewayConfig $gatewayConfig) {
        parent::__construct($context, $data);
        $this->gatewayConfig = $gatewayConfig;
    }
              
    public function getEmbeddedCss() {
        return $this->gatewayConfig->getEmbeddedCss();
    }

    public function hasCustomCss() {

    	// Get the custom CSS file
		$custom_css_file = $this->_scopeConfig->getValue('payment/checkout_com/checkout_com_embedded_design_settings/custom_css');

		// Determine if there is a custom CSS file
		return (bool) (isset($custom_css_file) && !empty($custom_css_file));
    }

    public function getEmbeddedUrl() {
        return $this->gatewayConfig->getEmbeddedUrl();
    }
}
