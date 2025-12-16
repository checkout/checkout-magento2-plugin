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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CkoSdkListener extends Field
{

    protected function _getElementHtml(AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);
        $js = <<<JS
<script type="text/javascript">
require(['jquery'], function ($) {
    $(function () {
        var sdkSelector = $('.cko_sdk_selector');
        var appleField = $('.cko_apple_pay_enabled_on_all_browsers');

        if (!sdkSelector.length || !appleField.length) {
            return;
        }

        sdkSelector.on('change', sync);

        function sync() {

            var sdkVal = sdkSelector.val();
            if (sdkVal === '0') {
                appleField.val('1').trigger('change');
            } 
        }
    });
});
</script>
JS;
        return $html . $js;
    }
}
