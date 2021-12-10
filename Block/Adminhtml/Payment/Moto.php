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

namespace CheckoutCom\Magento2\Block\Adminhtml\Payment;

use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc;
use Magento\Payment\Model\Config as PaymentModelConfig;

/**
 * Class Moto
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class Moto extends Cc
{
    /**
     * $_template field
     *
     * @var string $_template
     */
    public $_template = 'CheckoutCom_Magento2::payment/moto.phtml';
    /**
     * $paymentModelConfig field
     *
     * @var PaymentModelConfig $paymentModelConfig
     */
    public $paymentModelConfig;
    /**
     * $adminQuote field
     *
     * @var Quote $adminQuote
     */
    public $adminQuote;
    /**
     * $config field
     *
     * @var GatewayConfig $config
     */
    public $config;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    public $vaultHandler;
    /**
     * $cardHandler field
     *
     * @var CardHandlerService $cardHandler
     */
    public $cardHandler;

    /**
     * Moto constructor
     *
     * @param Context             $context
     * @param PaymentModelConfig   $paymentModelConfig
     * @param Quote               $adminQuote
     * @param GatewayConfig        $config
     * @param VaultHandlerService $vaultHandler
     * @param CardHandlerService  $cardHandler
     */
    public function __construct(
        Context $context,
        PaymentModelConfig $paymentModelConfig,
        Quote $adminQuote,
        GatewayConfig $config,
        VaultHandlerService $vaultHandler,
        CardHandlerService $cardHandler
    ) {
        parent::__construct($context, $paymentModelConfig);

        $this->adminQuote   = $adminQuote;
        $this->config        = $config;
        $this->vaultHandler = $vaultHandler;
        $this->cardHandler  = $cardHandler;
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
        return $this->config->getValue('saved_cards_enabled', 'checkoutcom_moto') && $this->config->getValue(
                'active',
                'checkoutcom_moto'
            ) && $this->vaultHandler->userHasCards($customerId);
    }

    /**
     * Description getUserCards function
     *
     * @return array
     */
    public function getUserCards()
    {
        // Get the customer id
        $customerId = $this->adminQuote->getQuote()->getCustomer()->getId();

        // Return the cards list
        return $this->vaultHandler->getUserCards($customerId);
    }
}
