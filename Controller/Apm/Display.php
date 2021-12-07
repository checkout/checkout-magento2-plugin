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

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Display
 */
class Display extends Action
{
    /**
     * $context field
     *
     * @var Context $context
     */
    public $context;
    /**
     * $pageFactory field
     *
     * @var PageFactory $pageFactory
     */
    public $pageFactory;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    public $jsonFactory;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    public $quoteHandler;

    /**
     * Display constructor
     *
     * @param Context             $context
     * @param PageFactory         $pageFactory
     * @param JsonFactory         $jsonFactory
     * @param Config              $config
     * @param QuoteHandlerService $quoteHandler
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        JsonFactory $jsonFactory,
        Config $config,
        QuoteHandlerService $quoteHandler
    ) {
        parent::__construct($context);

        $this->pageFactory  = $pageFactory;
        $this->jsonFactory  = $jsonFactory;
        $this->config       = $config;
        $this->quoteHandler = $quoteHandler;
    }

    /**
     * Description execute function
     *
     * @return Json
     */
    public function execute()
    {
        // Prepare the output
        $html      = '';
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

        return $this->jsonFactory->create()->setData(['html' => $html, 'apms' => $available]);
    }

    /**
     * Check if an APM is valid for display
     *
     * @param $apm
     * @param $apmEnabled
     * @param $billingAddress
     *
     * @return bool
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
        && $this->countryCurrencyMapping(
            $apm,
            $billingAddress['country_id'],
            $this->quoteHandler->getQuoteCurrency()
        );
    }

    /**
     * Check for specific country & currency mappings
     *
     * @param $apm
     * @param $billingCountry
     * @param $currency
     *
     * @return bool
     */
    public function countryCurrencyMapping($apm, $billingCountry, $currency)
    {
        if ($apm['value'] == 'klarna' || $apm['value'] == 'poli') {
            //            var_dump($currency);
            //            var_dump($billingCountry);
            //            die();
            if (strpos(
                    $apm['mappings'][$currency],
                    $billingCountry
                ) !== false) {
                return true;
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * Generate an APM block
     *
     * @param $apmId
     * @param $title
     *
     * @return string
     */
    public function loadBlock($apmId, $title)
    {
        return $this->pageFactory->create()
            ->getLayout()
            ->createBlock('CheckoutCom\Magento2\Block\Apm\Form')
            ->setTemplate('CheckoutCom_Magento2::payment/apm/' . $apmId . '.phtml')
            ->setData('apm_id', $apmId)
            ->setData('title', $title)
            ->toHtml();
    }
}
