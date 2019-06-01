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

namespace CheckoutCom\Magento2\Block\Adminhtml\Payment;

class Moto extends \Magento\Payment\Block\Form\Cc
{
    /**
     * @var String
     */
    protected $_template;

    /**
     * @var Config
     */
    protected $paymentModelConfig;
 
    /**
     * @var Quote
     */
    protected $adminQuote;

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
    protected function _toHtml()
    {
        $this->_eventManager->dispatch('payment_form_block_to_html_before', ['block' => $this]);
        return parent::_toHtml();
    }

    /**
     * Checks if saved cards can be displayed.
     *
     * @return bool
     */
    public function canDisplayCards() {
        // Get the customer id
        $customerId = $this->adminQuote->getQuote()->getCustomer()->getId();

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/cid.log');
$logger = new \Zend\Log\Logger();
$logger->addWriter($writer);
$logger->info(print_r($customerId, 1));

        // Return the check result
        return $this->config->getValue('saved_cards_enabled', 'checkoutcom_moto')
        && $this->vaultHandler->userHasCards($customerId);
    }

    /**
     * Get the saved cards for a customer.
     *
     * @return bool
     */
    public function getUserCards() {
        // Get the customer id
        $customerId = $this->adminQuote->getQuote()->getCustomer()->getId();

        // Return the cards list
        return $this->vaultHandler->getUserCards($customerId);
    }
}