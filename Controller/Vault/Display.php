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

namespace CheckoutCom\Magento2\Controller\Vault;

/**
 * Class Display
 */
class Display extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Display constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->vaultHandler = $vaultHandler;
        $this->logger = $logger;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        $html = '';
        try {
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $this->jsonFactory->create()->setData(
                ['html' => $html]
            );
        }
    }

    private function loadBlock($card)
    {
        try {
            return $this->pageFactory->create()->getLayout()
                ->createBlock('CheckoutCom\Magento2\Block\Vault\Form')
                ->setTemplate('CheckoutCom_Magento2::payment/vault/card.phtml')
                ->setData('card', $card)
                ->toHtml();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }
    }
}
