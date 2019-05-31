<?php

namespace CheckoutCom\Magento2\Controller\Apm;

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
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * Display constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    ) {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;


    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        $html = '';
        if ($this->getRequest()->isAjax()) {
            // Get the list of APM
            $apmEnabled = explode(',',
                $this->config->getValue('apm_enabled', 'checkoutcom_apm')
            );

            $apms = $this->config->getApms();

            // Load block data for each APM
            foreach ($apms as $amp) {

                if(in_array($amp['value'], $apmEnabled) && strpos($amp['currencies'], $this->quoteHandler->getQuoteCurrency()) !== false) {
                    $html .= $this->loadBlock($amp['value'], $amp['label']);
                }

            }

        }

        return $this->jsonFactory->create()->setData(
            ['html' => $html]
        );
    }

    private function loadBlock($apmId, $title)
    {
        return $this->pageFactory->create()->getLayout()
        ->createBlock('CheckoutCom\Magento2\Block\Apm\Form')
        ->setTemplate('CheckoutCom_Magento2::payment/apm/' . $apmId . '.phtml')
        ->setData('apm_id', $apmId)
        ->setData('title', $title)
        ->toHtml();
    }
}
