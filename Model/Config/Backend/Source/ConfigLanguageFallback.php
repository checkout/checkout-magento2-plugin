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
 * Class ConfigLanguageFallback
 */
class ConfigLanguageFallback implements OptionSourceInterface
{
    /**
     * Language fallback
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'EN-GB',
                'label' => __('English')
            ],
            [
                'value' => 'ES-ES',
                'label' => __('Spanish')
            ],
            [
                'value' => 'DE-DE',
                'label' => __('German')
            ],
            [
                'value' => 'KR-KR',
                'label' => __('Korean')
            ],
            [
                'value' => 'FR-FR',
                'label' => __('French')
            ],
            [
                'value' => 'IT-IT',
                'label' => __('Italian')
            ],
            [
                'value' => 'NL-NL',
                'label' => __('Dutch')
            ]
        ];
    }
}
