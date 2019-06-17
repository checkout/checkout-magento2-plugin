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

namespace CheckoutCom\Magento2\Controller\Apm;

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
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

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
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
        $this->logger = $logger;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        // Prepare the output
        $html = '';

        // Process the request
        try {
            if ($this->getRequest()->isAjax()) {
                // Get the list of APM
                $apmEnabled = explode(
                    ',',
                    $this->config->getValue('apm_enabled', 'checkoutcom_apm')
                );

                $apms = $this->config->getApms();

                // Load block data for each APM
                foreach ($apms as $apm) {
                    if ($this->isValidApm($apm, $apmEnabled)) {
                        $html .= $this->loadBlock($apm['value'], $apm['label']);
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

    private function isValidApm($apm, $apmEnabled) {
        return in_array($apm['value'], $apmEnabled)
        && strpos(
            $apm['currencies'],
            $this->quoteHandler->getQuoteCurrency()
        ) !== false;
    }

    private function loadBlock($apmId, $title)
    {
        try {
            return $this->pageFactory->create()->getLayout()
                ->createBlock('CheckoutCom\Magento2\Block\Apm\Form')
                ->setTemplate('CheckoutCom_Magento2::payment/apm/' . $apmId . '.phtml')
                ->setData('apm_id', $apmId)
                ->setData('title', $title)
                ->toHtml();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }
    }
}
