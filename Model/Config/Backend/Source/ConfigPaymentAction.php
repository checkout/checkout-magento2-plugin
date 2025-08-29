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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ConfigPaymentAction
 */
class ConfigPaymentAction implements OptionSourceInterface
{
    public const PAYMENT_ACTION_AUTHORIZE_CONFIG_VALUE = 'authorize';
    public const PAYMENT_ACTION_CAPTURE_CONFIG_VALUE = 'authorize_capture';

    public const PAYMENT_ACTION_AUTHORIZE_CONFIG_LABEL = 'Authorize';
    public const PAYMENT_ACTION_CAPTURE_CONFIG_LABEL = 'Authorize and Capture';
    
    /**
     * Options getter
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'authorize',
                'label' => __('Authorize')
            ],
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
