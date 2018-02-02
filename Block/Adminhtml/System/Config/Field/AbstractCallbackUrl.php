<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

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
