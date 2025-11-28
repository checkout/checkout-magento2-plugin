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

use CheckoutCom\Magento2\Provider\ApplePaymentSettings;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class EnableForAllBrowserMigrator
{
    protected FlowGeneralSettings $flowGeneralSettings;
    protected WriterInterface $configWriter;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        FlowGeneralSettings $flowGeneralSettings,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->flowGeneralSettings = $flowGeneralSettings;
        $this->configWriter = $configWriter;
    }

    /**
     * @throws LocalizedException
    */
    public function checkEnableForAllBrowser(int $website = 0): void
    {
        $scope = $website !== 0 ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        if ($this->flowGeneralSettings->useFlow((string) $website)) {
            $this->configWriter->save(
                ApplePaymentSettings::CONFIG_ENABLED_ON_ALL_BROWSER,
                '0',
                $scope,
                $website
            );
        };

    }
}
