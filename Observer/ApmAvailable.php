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

namespace CheckoutCom\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ApmAvailable.
 */
class ApmAvailable implements ObserverInterface
{
    /**
     * @var Config
     */
    public $config;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var Display
     */
    public $display;

    /**
     * ApmAvailable constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Controller\Apm\Display $display
    ) {
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
        $this->display = $display;
    }

    /**
     * Run the observer.
     */
    public function execute(Observer $observer)
    {
        if($observer->getEvent()->getMethodInstance()->getCode()=="checkoutcom_apm"){
            $enabled = false;
            
            // Get the list of enabled apms.
            $apmEnabled = explode(
                ',',
                $this->config->getValue('apm_enabled', 'checkoutcom_apm')
            );

            $apms = $this->config->getApms();
            $billingAddress = $this->quoteHandler->getBillingAddress()->getData();

            if (isset($billingAddress['country_id'])) {
                foreach ($apms as $apm) {
                    if ($this->display->isValidApm($apm, $apmEnabled, $billingAddress)) {
                        $enabled = true;
                    }
                }
            }

            $checkResult = $observer->getEvent()->getResult();

            if (!$enabled) {
                // If no valid apm, Don't show apm as a payment method.
                $checkResult->setData('is_available', false);    
            }
        }
    }
}
