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

namespace CheckoutCom\Magento2\Provider;

use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigSdk;

class FlowGeneralSettings extends AbstractSettingsProvider
{

    public const CONFIG_SDK = 'settings/checkoutcom_configuration/sdk';
    public const SALES_ATTRIBUTE_SHOULD_SAVE_CARD = 'cko_save_card';

    public function useFlow(?string $websiteCode): bool
    {
        return $this->getSdk($websiteCode) === ConfigSdk::SDK_FLOW_CONFIG_VALUE;
    }

    public function useFrames(?string $websiteCode): bool
    {
        return $this->getSdk($websiteCode) === ConfigSdk::SDK_FRAMES_CONFIG_VALUE;
    }

    public function getSdk(?string $websiteCode): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_SDK,
            $websiteCode,
        );
    }
}
