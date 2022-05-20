<?php

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
    public const SERVICE_ABC = 'ABC';
    /**
     * NAS service name
     */
    public const SERVICE_NAS = 'NAS';
    /**
     * Service config path
     */
    public const SERVICE_CONFIG_PATH = 'settings/checkoutcom_configuration/service';
    /**
     * Bearer key
     */
    public const BEARER_KEY = 'Bearer ';

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
