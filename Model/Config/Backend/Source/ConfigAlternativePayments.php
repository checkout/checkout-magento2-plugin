<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use \CheckoutCom\Magento2\Model\Methods\AlternativePaymentMethod;

/**
 * Class ConfigAlternativePayments
 */
class ConfigAlternativePayments implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {

        $list = array();
        foreach (AlternativePaymentMethod::PAYMENT_LIST as $key => $value) {
            $list []= array(
                'value' => $key,
                'label' => $value
            );
        }

        return $list;

    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return AlternativePaymentMethod::PAYMENT_LIST;
    }
}
