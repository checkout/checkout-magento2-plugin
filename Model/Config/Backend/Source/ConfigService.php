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
 * Class ConfigService
 */
class ConfigService implements OptionSourceInterface
{
    /**
     * ABC service name
     */
    public const string SERVICE_ABC = 'ABC';
    /**
     * NAS service name
     */
    public const string SERVICE_NAS = 'NAS';
    /**
     * Service config path
     */
    public const string SERVICE_CONFIG_PATH = 'settings/checkoutcom_configuration/service';
    /**
     * Bearer key
     */
    public const string BEARER_KEY = 'Bearer ';

    /**
     * Service config
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => 'ABC',
                'value' => self::SERVICE_ABC
            ],
            [
                'label' => 'NAS',
                'value' => self::SERVICE_NAS
            ]
        ];
    }
}
