<?php

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Logo extends Template implements RendererInterface {

    /**
     * Renders form element as HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element) {
        $pattern    = '<div id="checkout_com_adminhtml_logo"><a href="%s" target="_blank"><img src="%s" alt="Checkout.com Logo"></a></div>';
        $url        = 'https://checkout.com';
        $src        = $this->getViewFileUrl('CheckoutCom_Magento2::images/checkout_com_logo.png', ['area' => 'adminhtml']);

        return sprintf($pattern, $url, $src);
    }

}
