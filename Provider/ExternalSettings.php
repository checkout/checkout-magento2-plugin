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

namespace CheckoutCom\Magento2\Provider;

use CheckoutCom\Magento2\Provider\AbstractSettingsProvider;
use Exception;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ExternalSettings extends AbstractSettingsProvider {

    public const CONFIG_STORE_NAME = 'general/store_information/name';
    public const CONFIG_STORE_LOCALE = 'general/locale/code';

    private StoreManagerInterface $storeManager;
    protected LoggerInterface $logger;

    public function __construct (
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($scopeConfig);

        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function getStoreName(?string $storeCode): ?string
    {
        $storeNameFromConfiguration =  $this->getStoreLevelConfiguration(
            $storeCode,
            self::CONFIG_STORE_NAME
        );
        try {
            return !empty($storeNameFromConfiguration) ? trim($storeNameFromConfiguration) : $this->storeManager->getStore()->getName();
        } catch (Exception $error) {
            $this->logger->error(
                sprintf("Unable to fetch store code or website code: %s", $error->getMessage()),
            );

            return null;
        }
    }

    public function getStoreLocale(?string $storeCode): ?string
    {
        return $this->getStoreLevelConfiguration(
            self::CONFIG_STORE_LOCALE,
            $storeCode,
        );
    }

    public function getStoreCountry(?string $storeCode): ?string
    {
        return $this->getStoreLevelConfiguration(
            Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT,
            $storeCode,
        );
    }
}
