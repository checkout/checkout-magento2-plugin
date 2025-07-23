<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ConfigAdditionalLogging
 */
class ConfigAdditionalLogging implements OptionSourceInterface
{
    /**
     * WEBHOOK constant
     */
    const string WEBHOOK = 'webhook';
    /**
     * WEBHOOK constant
     */
    const string AUTH_KEYS = 'auth';
    /**
     * QUOTE_OBJECT constant
     */
    const string QUOTE_OBJECT = 'quote';
    /**
     * ORDER_OBJECT constant
     */
    const string ORDER_OBJECT = 'order';
    /**
     * PAYMENT_REQUEST constant
     */
    const string PAYMENT_REQUEST = 'payment';
    /**
     * ADMIN_ACTIONS constant
     */
    const string ADMIN_ACTIONS = 'api';

    /**
     * Additional logging options
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::WEBHOOK,
                'label' => __('Webhook'),
            ],
            [
                'value' => self::AUTH_KEYS,
                'label' => __('Authorisation Keys'),
            ],
            [
                'value' => self::QUOTE_OBJECT,
                'label' => __('Quote'),
            ],
            [
                'value' => self::ORDER_OBJECT,
                'label' => __('Order'),
            ],
            [
                'value' => self::PAYMENT_REQUEST,
                'label' => __('Payment Request'),
            ],
            [
                'value' => self::ADMIN_ACTIONS,
                'label' => __('API Response'),
            ],
        ];
    }
}
