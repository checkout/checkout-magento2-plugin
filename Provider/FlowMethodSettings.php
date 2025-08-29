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

use CheckoutCom\Magento2\Gateway\Config\Loader;
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
    public const CONFIG_FLOW_PAYMENT_APM_METHODS = "payment/checkoutcom_apm/apm_flow_enabled";

    private const GLOBAL_METHOD_LIST = [
        'klarna' => 'payment/checkoutcom_klarna/active',
        'googlepay' => 'payment/checkoutcom_google_pay/active',
        'applepay' => 'payment/checkoutcom_apple_pay/active',
        'paypal' => 'payment/checkoutcom_paypal/active',
        'card' => 'payment/checkoutcom_card_payment/active'
    ];

    private $designList = [
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_DEFAULT_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_DEFAULT_CONTENT,
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_GRAPEFRUIT_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_GRAPEFRUIT_CONTENT,
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_MIDNIGHT_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_MIDNIGHT_CONTENT,
        ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_SIMPLICITY_CONFIG_VALUE => ConfigFlowPredefinedDesign::PREDEFINED_DESIGN_SIMPLICITY_CONTENT
    ];

    private FlowGeneralSettings $flowGeneralSettings;
    private Loader $configLoader;

    public function __construct(
        FlowGeneralSettings $flowGeneralSettings,
        Loader $configLoader,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->flowGeneralSettings = $flowGeneralSettings;

        parent::__construct(
            $scopeConfig
        );
        $this->configLoader = $configLoader;
    }

    public function isAvailable(?string $website): bool
    {
        $isActive = $this->isActive($website);

        return $isActive === "1" && $this->flowGeneralSettings->useFlow($website);
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

    public function getDesign($store): string
    {
        return
            $this->getDesignSelector($store) === ConfigFlowWidgetDesignSelector::CUSTOM_DESIGN_CONFIG_VALUE ?
                $this->getCustomDesign($store) :
                $this->getPredefinedDesignValue($store);
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

    public function getPredefinedDesignValue(?string $store): string
    {
        $design = $this->getPredefinedDesign($store);
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

    //TODO : Filter allowed methods by country and currency for a given quote
    public function getAllowedPaymentMethods(?string $storeCode): array
    {
        return array_merge($this->getSelectedApmMethods($storeCode), $this->getGlobalMethodByStatus(true, $storeCode));
    }

    public function getDisabledPaymentMethods(?string $storeCode): array
    {
        $apmList = $this->configLoader->loadApmList(Loader::APM_FLOW_FILE_NAME);
        $methodCodes = [];
        foreach ($apmList as $apm) {
            $methodCodes[] = $apm['value'];
        }

        return array_merge(array_diff($methodCodes, $this->getSelectedApmMethods($storeCode)), $this->getGlobalMethodByStatus(false, $storeCode));
    }

    // TODO : Adjust apm_flow.xml with doc : https://api-reference.checkout.com/#operation/CreatePaymentSession
    private function getSelectedApmMethods(?string $storeCode): array
    {
        return explode(',', (string)$this->getStoreLevelConfiguration(
            self::CONFIG_FLOW_PAYMENT_APM_METHODS,
            $storeCode,
        ));
    }

    private function getGlobalMethodByStatus(bool $isActive, ?string $storeCode): array
    {
        $mehods = [];
        foreach (self::GLOBAL_METHOD_LIST as $code => $configPath) {
            if ((bool)$this->getStoreLevelConfiguration($configPath, $storeCode) === $isActive) {
                $mehods[] = $code;
            }
        }
        return $mehods;
    }

}
