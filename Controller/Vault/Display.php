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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Vault;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Vault\Model\PaymentToken;

/**
 * Class Display
 */
class Display extends Action
{
    /**
     * $pageFactory field
     *
     * @var PageFactory $pageFactory
     */
    private $pageFactory;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    private $jsonFactory;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    private $vaultHandler;

    /**
     * Display constructor
     *
     * @param Context             $context
     * @param PageFactory         $pageFactory
     * @param JsonFactory         $jsonFactory
     * @param Config              $config
     * @param VaultHandlerService $vaultHandler
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        JsonFactory $jsonFactory,
        Config $config,
        VaultHandlerService $vaultHandler
    ) {
        parent::__construct($context);

        $this->pageFactory  = $pageFactory;
        $this->jsonFactory  = $jsonFactory;
        $this->config       = $config;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Handles the controller method
     *
     * @return Json
     */
    public function execute(): Json
    {
        $html = '';
        if ($this->getRequest()->isAjax()) {
            // Check if vault is enabled
            $vaultEnabled = $this->config->getValue('active', 'checkoutcom_vault');

            // Load block data for vault
            if ($vaultEnabled) {
                // Get the uer cards
                $cards = $this->vaultHandler->getUserCards();
                foreach ($cards as $card) {
                    $html .= $this->loadBlock($card);
                }
            }
        }

        return $this->jsonFactory->create()->setData(['html' => $html]);
    }

    /**
     * Description loadBlock function
     *
     * @param PaymentToken $card
     *
     * @return string
     */
    private function loadBlock(PaymentToken $card): string
    {
        return $this->pageFactory->create()
            ->getLayout()
            ->createBlock('CheckoutCom\Magento2\Block\Vault\Form')
            ->setTemplate('CheckoutCom_Magento2::payment/vault/card.phtml')
            ->setData('card', $card)
            ->toHtml();
    }
}
