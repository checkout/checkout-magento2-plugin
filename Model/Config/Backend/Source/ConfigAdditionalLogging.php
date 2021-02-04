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
 * Class ConfigAdditionalLogging
 */
class ConfigAdditionalLogging implements \Magento\Framework\Data\OptionSourceInterface
{

    const WEBHOOK = 'webhook';
    const AUTH_KEYS = 'auth';
    const QUOTE_OBJECT = 'quote';
    const ORDER_OBJECT = 'order';
    const PAYMENT_REQUEST = 'payment';
    const ADMIN_ACTIONS = 'api';

    /**
     * Additional logging options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::WEBHOOK,
                'label' => __('Webhook')
            ],
            [
                'value' => self::AUTH_KEYS,
                'label' => __('Authorisation Keys')
            ],
            [
                'value' => self::QUOTE_OBJECT,
                'label' => __('Quote')
            ],
            [
                'value' => self::ORDER_OBJECT,
                'label' => __('Order')
            ],
            [
                'value' => self::PAYMENT_REQUEST,
                'label' => __('Payment Request')
            ],
            [
                'value' => self::ADMIN_ACTIONS,
                'label' => __('API Response')
            ]
        ];
    }
}
