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

namespace CheckoutCom\Magento2\ViewModel\Flow;

use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use CheckoutCom\Magento2\Helper\Logger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class LoadMainScript
 */
class LoadMainScript implements ArgumentInterface
{
    private FlowGeneralSettings $flowSettings;
    private Logger $logger;
    private StoreManagerInterface $storeManager;

    public function __construct(
        FlowGeneralSettings $flowSettings,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->flowSettings = $flowSettings;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    public function useFlow(): bool
    {
        $useFlow = false;
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $useFlow = $this->flowSettings->useFlow($websiteCode);
        } catch (NoSuchEntityException $e) {
            $useFlow = $useFlow = $this->flowSettings->useFlow(null);
            $this->logger->write(sprintf("Error getting website code: %s", $e->getMessage()));
        }

        return $useFlow;
    }
}
