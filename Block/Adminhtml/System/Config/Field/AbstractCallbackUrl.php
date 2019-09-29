<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

abstract class AbstractCallbackUrl extends \Magento\Config\Block\System\Config\Form\Field {
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
