<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{

    const ACTION_AUTHORIZE = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * Possible actions on order place
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            [
                'value' => self::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => self::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture'),
            ]
        ];
    }
}
