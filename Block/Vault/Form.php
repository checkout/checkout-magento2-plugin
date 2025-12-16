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

namespace CheckoutCom\Magento2\Block\Vault;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Form
 */
class Form extends Template
{
    public CardHandlerService $cardHandler;
    public VaultHandlerService $vaultHandler;
    public Config $config;

    public function __construct(
        Context $context,
        CardHandlerService $cardHandler,
        VaultHandlerService $vaultHandler,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->cardHandler = $cardHandler;
        $this->vaultHandler = $vaultHandler;
        $this->config = $config;
    }
}
