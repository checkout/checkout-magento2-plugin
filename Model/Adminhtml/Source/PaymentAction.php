<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{
    /**
     * Possible actions on order place
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => 'authorize',
                'label' => __('Authorize'),
            ],
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture'),
            ]
        ];
    }
}
