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

use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigFlowPredefinedDesign;
use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigFlowWidgetDesignSelector;
use Magento\Framework\App\Config\ScopeConfigInterface;

class FlowMethodSettings extends AbstractSettingsProvider
{

    public const CONFIG_FLOW_PAYMENT_ACTIVE = 'payment/checkoutcom_flow/active';
    public const CONFIG_FLOW_PAYMENT_TITLE = 'payment/checkoutcom_flow/title';
    public const CONFIG_FLOW_PAYMENT_SORT_ORDER = 'payment/checkoutcom_flow/sort_order';
    public const CONFIG_FLOW_PAYMENT_DESIGN_SELECTOR = 'payment/checkoutcom_flow/widget_design_selector';
    public const CONFIG_FLOW_PAYMENT_PREDEFINED_WIDGET_DESIGN = 'payment/checkoutcom_flow/predefined_widget_design';
    public const CONFIG_FLOW_PAYMENT_CUSTOM_WIDGET_DESIGN = 'payment/checkoutcom_flow/custom_widget_design';

    private $designList = [
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_DEFAULT_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_DEFAULT_CONTENT,
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_GRAPEFRUIT_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_GRAPEFRUIT_CONTENT,
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_MIDNIGHT_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_MIDNIGHT_CONTENT,
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_SIMPLICITY_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_SIMPLICITY_CONTENT
    ];

    private FlowGeneralSettings $flowGeneralSettings;

    public function __construct(
        FlowGeneralSettings $flowGeneralSettings,
        ScopeConfigInterface $scopeConfig,
    ) {
        parent::__construct(
            $scopeConfig
        );
        $this->flowGeneralSettings = $flowGeneralSettings;
    }

    public function isAvailable(?string $websiteCode): bool
    {
        $isActive = $this->isActive($websiteCode);

        return $isActive === "1" && $this->flowGeneralSettings->useFlow($websiteCode);
    }

    public function isActive(?string $websiteCode): ?string
    {
        return $this->getWebsiteLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_ACTIVE,
            $websiteCode,
        );
    }

    public function getTitle(?string $storeCode): ?string
    {
        return $this->getStoreLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_TITLE,
            $storeCode,
        );
    }

    public function getSortOrder(?string $storeCode): ?string
    {
        return $this->getStoreLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_SORT_ORDER,
            $storeCode,
        );
    }

    public function getDesign(?string $storeCode): string
    {
        return
            $this->getDesignSelector($storeCode) === ConfigFlowWidgetDesignSelector::CUSTOM_DESIGN_CONFIG_VALUE ?
            $this->getCustomDesign($storeCode) :
            $this->getPredefinedDesignValue($storeCode);
    }

    public function getDesignSelector(?string $storeCode): ?string
    {
        return $this->getStoreLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_DESIGN_SELECTOR,
            $storeCode,
        );
    }

    public function getCustomDesign(?string $storeCode): string
    {
        return $this->getStoreLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_CUSTOM_WIDGET_DESIGN,
            $storeCode,
        ) ?? '';
    }

    public function getPredefinedDesignValue(?string $storeCode): string
    {
        $design = $this->getPredefinedDesign($storeCode) ?? '';

        return $this->getDesignValue($design);
    }

    public function getPredefinedDesign(?string $storeCode): ?string
    {
        return $this->getStoreLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_PREDEFINED_WIDGET_DESIGN,
            $storeCode,
        );
    }

    protected function getDesignValue(string $designName): string
    {
        return $this->designList[$designName] ?? '';
    }
}
