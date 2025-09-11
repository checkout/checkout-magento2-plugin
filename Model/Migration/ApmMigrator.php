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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Migration;

use CheckoutCom\Magento2\Gateway\Config\Loader;
use CheckoutCom\Magento2\Provider\FlowPaymentMethodSettings;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

class ApmMigrator
{
    protected Loader $configLoader;
    protected ScopeConfigInterface $scopeConfig;
    protected WriterInterface $configWriter;

    public function __construct(
        Loader $configLoader,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
    ) {
        $this->configLoader = $configLoader;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    public function migrate(?int $websiteId = null)
    {
        if (!empty($websiteId)) {
            $this->updateConfig($websiteId);

            return;
        }

        $this->updateConfig();
    }

    protected function updateConfig(int $scopeId = 0): void
    {
        $scope = $scopeId !== 0 ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        if ($this->preventUpdate($scope, $scopeId)) {
            return;
        }

        $oldApmEnabled = $this->scopeConfig->getValue(
            FlowPaymentMethodSettings::CONFIG_PAYMENT_OLD_APM_METHODS_LIST,
            $scope,
            $scopeId
        );

        $newApmEnabled = $this->mapWithNewValue($oldApmEnabled);

        if (empty($newApmEnabled)) {
            return;
        }

        $this->configWriter->save(
            FlowPaymentMethodSettings::CONFIG_FLOW_PAYMENT_APM_METHODS_LIST,
            $newApmEnabled,
            $scope,
            $scopeId
        );
    }

    protected function preventUpdate(string $scope, int $scopeId = 0): bool
    {
        $currentConfiguration = $this->scopeConfig->getValue(
            FlowPaymentMethodSettings::CONFIG_FLOW_PAYMENT_APM_METHODS_LIST,
            $scope,
            $scopeId
        );

        if (!empty($currentConfiguration)) {
            return true;
        }

        if ($scopeId !== 0) {
            $defaultValue = $this->scopeConfig->getValue(
            FlowPaymentMethodSettings::CONFIG_PAYMENT_OLD_APM_METHODS_LIST,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );

            $websiteValue = $this->scopeConfig->getValue(
                FlowPaymentMethodSettings::CONFIG_PAYMENT_OLD_APM_METHODS_LIST,
                $scope,
                $scopeId
            );

            return $defaultValue === $websiteValue;
        }

        return false;
    }

    protected function mapWithNewValue(string $oldConfiguration): string
    {
        $apmList = $this->configLoader->loadApmList(Loader::APM_FLOW_FILE_NAME);
        $oldApmSelected = explode(',', $oldConfiguration);

        $newConfiguration = [];
        foreach ($apmList as $apm) {
            if (in_array($apm['oldApm'], $oldApmSelected)) {
                $newConfiguration[] = $apm['value'];
            }
        }

        return implode(',', $newConfiguration);
    }
}
