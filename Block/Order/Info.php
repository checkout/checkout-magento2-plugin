<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Block\Order;

use Magento\Sales\Model\Order\Address;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;

/**
 * @obsolete
 *
 * Invoice view comments form
 */
class Info extends \Magento\Sales\Block\Order\Info
{
    /**
     * @obsolete
     */
    protected function _construct()
    {
        // we override to keep the translation context correct
        $this->setModuleName($this->extractModuleName(\Magento\Sales\Block\Order\Info::class));

        parent::_construct();
    }
}
