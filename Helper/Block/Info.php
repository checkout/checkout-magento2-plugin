<?php

namespace CheckoutCom\Magento2\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo {

    /**
     * Returns translated label.
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field) {
        return __($field);
    }

}
