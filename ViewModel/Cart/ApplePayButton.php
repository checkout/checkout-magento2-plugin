<?php

declare(strict_types=1);

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

namespace CheckoutCom\Magento2\ViewModel\Cart;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ApplePayButton
 */
class ApplePayButton implements ArgumentInterface
{
    private Config $checkoutComConfig;
    private Logger $logger;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Config $checkoutComConfig,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->checkoutComConfig = $checkoutComConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    public function isApplePayEnabledForAllBrowsers(): bool
    {
        $isEnabled = false;
        try {
            $storeCode = $this->storeManager->getStore()->getCode();
            $isEnabled = (bool)$this->checkoutComConfig->getValue('enabled_on_all_browsers', 'checkoutcom_apple_pay', $storeCode, ScopeInterface::SCOPE_STORE);
        } catch (NoSuchEntityException $e) {
            $isEnabled = (bool)$this->checkoutComConfig->getValue('enabled_on_all_browsers', 'checkoutcom_apple_pay');
            $this->logger->write(sprintf("Error getting store code: %s", $e->getMessage()));
        }

        return $isEnabled;
    }
}
