<?php
/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 7
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2019 Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */

namespace CheckoutCom\Magento2\Block\Widgets;
  
class DatePicker extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var  Registry
     */
    protected $_coreRegistry;
  
    /**
     * @param Context  $context
     * @param Registry $coreRegistry
     * @param array    $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }
  
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Add configuration element
        $html = $element->getElementHtml();

        // Add icon on datepicker 
        $html .= '<button type="button" class="ui-datepicker-trigger '
            .'v-middle"><span>Select Date</span></button>';

        // Add datepicker with element by jquery
        $html .= '<script type="text/javascript">
        require(["jquery",
            "mage/calendar"
        ], function($){
            $("#' . $element->getHtmlId() . '").datetimepicker({
                    stepMinute: 1,
                    minDate: new Date(),
                    dateFormat:"dd-mm-yy"
                }
            );
        });
        </script>';
            
        // return datepicker element
        return $html;
    }
}