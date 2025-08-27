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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AbstractSettingsProvider
 */
class AbstractSettingsProvider {

    private ScopeConfigInterface $scopeConfig;
    
    public function __construct (
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
    }
    
    public function getWebsiteLevelConfiguration(string $path, ?string $websiteCode): ?string {
        if (!is_null($websiteCode)) {
            $config =  $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_WEBSITE,
                $websiteCode,
            );
            return $config;
        }

        return $this->scopeConfig->getValue(
            $path
        );
    }

    public function getStoreLevelConfiguration(string $path, ?string $storeCode): ?string {
        if (!is_null($storeCode)) {
            $config =  $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $storeCode,
            );
            return $config;
        }

        return $this->scopeConfig->getValue(
            $path
        );
    }

    public function getDefaultLevelConfiguration(string $path): ?string {
        return $this->scopeConfig->getValue(
            $path
        );
    }
}