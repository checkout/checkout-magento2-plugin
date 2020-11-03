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

/**
 * Class Display
 */
class Display extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Context
     */
    public $context;

    /**
     * @var PageFactory
     */
    public $pageFactory;

    /**
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

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
    public function execute()
    {
        // Prepare the output
        $html = '';
        $available = [];
        
        // Process the request
        if ($this->getRequest()->isAjax()) {
            // Get the list of APM
            $apmEnabled = explode(
                ',',
                $this->config->getValue('apm_enabled', 'checkoutcom_apm')
            );

            $apms = $this->config->getApms();

            // Load block data for each APM
            if ($this->getRequest()->getParam('country_id')) {
                $billingAddress = ['country_id' => $this->getRequest()->getParam('country_id')];
            } else {
                $billingAddress = $this->quoteHandler->getBillingAddress()->getData();
            }

            foreach ($apms as $apm) {
                if ($this->isValidApm($apm, $apmEnabled, $billingAddress)) {
                    $html .= $this->loadBlock($apm['value'], $apm['label']);
                    array_push($available, $apm['value']);
                }
            }
        }

        return $this->jsonFactory->create()->setData(
            ['html' => $html, 'apms' => $available]
        );
    }

    /**
     * Check if an APM is valid for display.
     *
     * @param string $apm
     * @param array $apmEnabled
     * @return boolean
     */
    public function isValidApm($apm, $apmEnabled, $billingAddress)
    {
        return in_array($apm['value'], $apmEnabled)
        && strpos(
            $apm['countries'],
            $billingAddress['country_id']
        ) !== false
        && strpos(
            $apm['currencies'],
            $this->quoteHandler->getQuoteCurrency()
        ) !== false
        && $this->countryCurrencyMapping($apm['value'], $billingAddress['country_id'], $this->quoteHandler->getQuoteCurrency());
    }

    /**
     * Check for specific country & currency mappings.
     *
     * @param string $apmValue
     * @param string $billingCountry
     * @param string $currency
     * @return boolean
     */
    public function countryCurrencyMapping($apmValue, $billingCountry, $currency)
    {
        if ($apmValue == 'poli') {
            if (($billingCountry == 'AU' && $currency == 'AUD')
                || ($billingCountry == 'NZ' && $currency == 'NZD')
            ) {
                return true;
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * Generate an APM block.
     *
     * @param string $apmId
     * @param string $title
     * @return string
     */
    public function loadBlock($apmId, $title)
    {
        return $this->pageFactory->create()->getLayout()
            ->createBlock('CheckoutCom\Magento2\Block\Apm\Form')
            ->setTemplate('CheckoutCom_Magento2::payment/apm/' . $apmId . '.phtml')
            ->setData('apm_id', $apmId)
            ->setData('title', $title)
            ->toHtml();
    }
}
