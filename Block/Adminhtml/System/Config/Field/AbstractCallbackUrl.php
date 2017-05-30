<?php

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

abstract class AbstractCallbackUrl extends Field {

    /**
     * Overridden method for rendering a field. In this case the field must be only for read.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) {
        $callbackUrl= $this->getBaseUrl() . 'checkout_com/' . $this->getControllerUrl();

        $element->setData('value', $callbackUrl);
        $element->setReadonly('readonly');

        return $element->getElementHtml();
    }

    /**
     * Returns the controller url.
     *
     * @return string
     */
    public abstract function getControllerUrl();

}
