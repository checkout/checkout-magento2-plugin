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

namespace CheckoutCom\Magento2\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class DeleteServiceConfiguration implements DataPatchInterface
{
    protected WriterInterface $configurationWriter;
    protected StoreManagerInterface $storeManager;

    private const SERVICE_PATH = 'settings/checkoutcom_configuration/service';

    public function __construct(
        WriterInterface $configurationWriter,
        StoreManagerInterface $storeManager
    ) {
        $this->configurationWriter = $configurationWriter;
        $this->storeManager = $storeManager;
    }

    public function apply(): self
    {
        $this->configurationWriter->delete(self::SERVICE_PATH);

        $websites = $this->storeManager->getWebsites();

        foreach ($websites as $website) {
            $id = (int) $website->getId();
            $this->configurationWriter->delete(self::SERVICE_PATH, ScopeInterface::SCOPE_WEBSITES, $id);
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases() 
    {
        return [];
    }
}