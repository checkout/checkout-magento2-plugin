<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

class ConfigApplePayCapabilities implements \Magento\Framework\Option\ArrayInterface {

    const CAP_CRE = 'supportsCredit';
    const CAP_DEB = 'supportsDebit';

    /**
     * Possible Apple Pay Cards
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::CAP_CRE,
                'label' => __('Credit cards')
            ],
            [
                'value' => self::CAP_DEB,
                'label' => __('Debit cards')
            ],
        ];
    }
}