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
 * Class ConfigGooglePayEnvironment
 */
class ConfigGooglePayEnvironment implements OptionSourceInterface
{
    /**
     * ENVIRONMENT_TEST constant
     *
     * @var string ENVIRONMENT_TEST
     */
    const ENVIRONMENT_TEST = 'TEST';
    /**
     * ENVIRONMENT_PRODUCTION constant
     *
     * @var string ENVIRONMENT_PRODUCTION
     */
    const ENVIRONMENT_PRODUCTION = 'PRODUCTION';

    /**
     * Possible environment types
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ENVIRONMENT_TEST,
                'label' => __('Test')
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }
}
