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

use CheckoutCom\Magento2\Provider\AbstractSettingsProvider;

class AccountSettings extends AbstractSettingsProvider {

    public const CONFIG_REGION = 'settings/checkoutcom_configuration/region';
    public const CONFIG_SERVICE = 'settings/checkoutcom_configuration/service';
    public const CONFIG_SECRET_KEY = 'settings/checkoutcom_configuration/secret_key';
    public const CONFIG_PUBLIC_KEY = 'settings/checkoutcom_configuration/public_key';
    public const CONFIG_PRIVATE_SHARED_KEY = 'settings/checkoutcom_configuration/private_shared_key';
    public const CONFIG_CHANNEL_ID = 'settings/checkoutcom_configuration/channel_id';

    public function getRegion(?string $website): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_REGION,
            $website
        );
    }

    public function getService(?string $website): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_SERVICE,
            $website
        );
    }

    public function getSecretKey(?string $website): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_SECRET_KEY,
            $website
        );
    }

    public function getPublicKey(?string $website): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_PUBLIC_KEY,
            $website
        );
    }

    public function getPrivateSharedKey(?string $website): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_PRIVATE_SHARED_KEY,
            $website
        );
    }

    public function getChannelId(?string $website): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_CHANNEL_ID,
            $website
        );
    }
}