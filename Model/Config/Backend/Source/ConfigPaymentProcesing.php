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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ConfigDefaultMethod
 */
class ConfigPaymentProcesing implements OptionSourceInterface
{
    /**
     * PAYMENT_FIRST constant
     *
     * @var string PAYMENT_FIRST
     */
    const PAYMENT_FIRST = 'payment_first';
    /**
     * ORDER_FIRST constant
     *
     * @var string ORDER_FIRST
     */
    const ORDER_FIRST = 'order_first';

    /**
     * Return the order status options
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        // Return the default array
        return  [
            [
                'value' => self::PAYMENT_FIRST,
                'label' => __('Payment first'),
            ],
            [
                'value' => self::ORDER_FIRST,
                'label' => __('Order first'),
            ],
        ];
    }
}
