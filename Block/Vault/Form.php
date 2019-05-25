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
     * @var CardHandlerService
     */
    public $cardHandler;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * Form constructor.
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->cardHandler = $cardHandler;
        $this->vaultHandler = $vaultHandler;         
    }
}