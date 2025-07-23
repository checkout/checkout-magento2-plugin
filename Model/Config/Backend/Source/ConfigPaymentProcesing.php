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
 * Class ConfigDefaultMethod
 */
class ConfigPaymentProcesing implements OptionSourceInterface
{
    /**
     * PAYMENT_FIRST constant
     */
    const string PAYMENT_FIRST = 'payment_first';
    /**
     * ORDER_FIRST constant
     */
    const string ORDER_FIRST = 'order_first';

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
