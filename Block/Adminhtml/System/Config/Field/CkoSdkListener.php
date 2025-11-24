<?php
namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CkoSdkListener extends Field
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

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
        function sync() {

            var sdkVal = sdkSelector.val();
            if (sdkVal === '0') {
                appleField.val('1').trigger('change');
            } 
        }
        sdkSelector.on('change', sync);
    });
});
</script>
JS;
        return $html . $js;
    }
}
