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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Plugin\Frontend;

/**
 * Class StoredCardsLink.
 */
class StoredCardsLink
{
    /**
     * Block name.
     */
    const BLOCK_NAME = 'stored-cards-link';

    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var Config
     */
    public $config;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->config = $config;
    }

    /**
     * Run the observer.
     */
    public function afterToHtml(\Magento\Framework\View\Element\AbstractBlock $subject, $result)
    {
        if (!$this->backendAuthSession->isLoggedIn()) {
            // Get the vault configuration state
            $vaultEnabled = $this->config->getValue('active', 'checkoutcom_vault');

            // Handle the block display
            $shouldHide = $subject->getNameInLayout() == self::BLOCK_NAME && !$vaultEnabled;
            if ($shouldHide) {
                return '';
            }
        }

        return $result;
    }
}
