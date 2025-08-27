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
use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigPaymentAction;

/**
 * Class GeneralSettings
 */
class GeneralSettings extends AbstractSettingsProvider {

    public const CONFIG_ENABLE_MODULE = "settings/checkoutcom_configuration/active";
    public const CONFIG_ENVIRONMENT = "settings/checkoutcom_configuration/environment";
    public const CONFIG_DEFAULT_ACTIVE_METHOD = "settings/checkoutcom_configuration/default_method";
    public const CONFIG_PAYMENT_PROCESSING = "settings/checkoutcom_configuration/payment_processing";
    public const CONFIG_PAYMENT_ACTION = "settings/checkoutcom_configuration/payment_action";
    public const CONFIG_CAPTURE_TIME = "settings/checkoutcom_configuration/capture_time";
    public const CONFIG_MIN_CAPTURE_TIME = "settings/checkoutcom_configuration/min_capture_time";
    public const CONFIG_ENABLE_DYNAMIC_DESCRIPTOR = "settings/checkoutcom_configuration/dynamic_descriptor_enabled";
    public const CONFIG_DESCRIPTOR_NAME= "settings/checkoutcom_configuration/descriptor_name";
    public const CONFIG_DESCRIPTOR_CITY = "settings/checkoutcom_configuration/descriptor_city";
    public const CONFIG_ENABLE_WEBHOOK_TABLE = "settings/checkoutcom_configuration/webhooks_table_enabled";
    public const CONFIG_ENABLE_WEBHOOK_TABLE_CLEAN = "settings/checkoutcom_configuration/webhooks_table_clean_enabled";
    public const CONFIG_CLEAN_WEBHOOK_ON = "settings/checkoutcom_configuration/webhooks_webhook_clean";
    
    public function isEnabled(?string $websiteCode): string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_ENABLE_MODULE,
            $websiteCode,
        );
    }

    public function getEnvironment(?string $websiteCode): string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_ENVIRONMENT,
            $websiteCode,
        );
    }

    public function getDefaultActiveMethod(?string $websiteCode): string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_DEFAULT_ACTIVE_METHOD,
            $websiteCode,
        );
    }

    public function getPaymentProcessing(?string $websiteCode): string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_PAYMENT_PROCESSING,
            $websiteCode,
        );
    }

    public function isAuthorizeAndCapture(?string $websiteCode): bool {
        return $this->getPaymentAction($websiteCode) === ConfigPaymentAction::PAYMENT_ACTION_CAPTURE_CONFIG_VALUE;
    }

    public function getPaymentAction(?string $websiteCode): string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_PAYMENT_ACTION,
            $websiteCode,
        );
    }

    public function getCaptureTime(?string $websiteCode): string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_CAPTURE_TIME,
            $websiteCode,
        );
    }

    public function getMinCaptureTime(?string $websiteCode): ?string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_MIN_CAPTURE_TIME,
            $websiteCode,
        );
    }

    public function isDynamicDescriptorEnabled(?string $websiteCode): ?string {
        return $this->getStoreLevelConfiguration(
            self::CONFIG_ENABLE_DYNAMIC_DESCRIPTOR,
            $websiteCode,
        );
    }

    public function getDynamicDescriptorName(?string $storeCode): ?string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_DESCRIPTOR_NAME,
            $storeCode,
        );
    }

    public function getDynamicDescriptorCity(?string $websiteCode): ?string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_DESCRIPTOR_CITY,
            $websiteCode,
        );
    }

    public function isWebhookTableEnabled(?string $websiteCode): ?string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_ENABLE_WEBHOOK_TABLE,
            $websiteCode,
        );
    }

    public function isWebhookTableCleanEnabled(?string $websiteCode): ?string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_ENABLE_WEBHOOK_TABLE_CLEAN,
            $websiteCode,
        );
    }

    public function getCleanWebhookOn(?string $websiteCode): ?string {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_CLEAN_WEBHOOK_ON,
            $websiteCode,
        );
    }
}