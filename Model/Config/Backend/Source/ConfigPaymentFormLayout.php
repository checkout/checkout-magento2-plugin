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

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

/**
 * Class ConfigPaymentFormLayout
 */
class ConfigPaymentFormLayout implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Payment form layout
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'single',
                'label' => __('Single iframe')
            ],
            [
                'value' => 'multi',
                'label' => __('Multiple iframes')
            ]
        ];
    }
}
