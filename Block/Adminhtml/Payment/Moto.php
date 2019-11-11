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

namespace CheckoutCom\Magento2\Block\Adminhtml\Payment;

/**
 * Class Moto
 */
class Moto extends \Magento\Payment\Block\Form\Cc
{
    /**
     * @var String
     */
    public $_template;

    /**
     * @var Config
     */
    public $paymentModelConfig;

    /**
     * @var Quote
     */
    public $adminQuote;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * @var CardHandlerService
     */
    public $cardHandler;

    /**
     * Moto constructor.
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentModelConfig,
        \Magento\Backend\Model\Session\Quote $adminQuote,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler
    ) {
        parent::__construct($context, $paymentModelConfig);

        $this->_template = 'CheckoutCom_Magento2::payment/moto.phtml';
        $this->adminQuote = $adminQuote;
        $this->config = $config;
        $this->vaultHandler = $vaultHandler;
        $this->cardHandler = $cardHandler;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        $this->_eventManager->dispatch('payment_form_block_to_html_before', ['block' => $this]);
        return parent::_toHtml();
    }

    /**
     * Checks if saved cards can be displayed.
     *
     * @return bool
     */
    public function canDisplayAdminCards()
    {
        // Get the customer id
        $customerId = $this->adminQuote->getQuote()->getCustomer()->getId();

        // Return the check result
        return $this->config->getValue('saved_cards_enabled', 'checkoutcom_moto')
        && $this->config->getValue('active', 'checkoutcom_moto')
        && $this->vaultHandler->userHasCards($customerId);
    }

    /**
     * Get the saved cards for a customer.
     *
     * @return bool
     */
    public function getUserCards()
    {
        // Get the customer id
        $customerId = $this->adminQuote->getQuote()->getCustomer()->getId();

        // Return the cards list
        return $this->vaultHandler->getUserCards($customerId);
    }
}
