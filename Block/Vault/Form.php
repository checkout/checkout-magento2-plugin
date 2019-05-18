<?php
/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 7
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2019 Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */

namespace CheckoutCom\Magento2\Block\Vault;

class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CcConfigProvider
     */
    private $iconsProvider;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * Form constructor.
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\CcConfigProvider $iconsProvider,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->iconsProvider = $iconsProvider;
        $this->vaultHandler = $vaultHandler;         
    }

    /**
     * Returns the url to the CC icon.
     *
     * @return string
     */
    public function getIconUrl($type) {
        return  $this->iconsProvider->getIcons()[$type]['url'];
    }

    /**
     * Returns the icon height in pixels.
     *
     * @return int
     */
    public function getIconHeight($type) {
        return  $this->iconsProvider->getIcons()[$type]['height'];
    }

    /**
     * Returns the icon width in pixels.
     *
     * @return int
     */
    public function getIconWidth($type) {
        return  $this->iconsProvider->getIcons()[$type]['width'];
    }
}