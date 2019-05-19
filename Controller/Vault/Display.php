<?php

namespace CheckoutCom\Magento2\Controller\Vault;

class Display extends \Magento\Framework\App\Action\Action {

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
     * Display constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
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

        return $this->jsonFactory->create()->setData(
            ['html' => $html]
        );
    }

    private function loadBlock($card)
    {
        return $this->pageFactory->create()->getLayout()
        ->createBlock('CheckoutCom\Magento2\Block\Vault\Form')
        ->setTemplate('CheckoutCom_Magento2::payment/vault/card.phtml')
        ->setData('card', $card)
        ->toHtml();
    }
}
