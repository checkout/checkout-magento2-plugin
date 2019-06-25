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

namespace CheckoutCom\Magento2\Helper;

use \Magento\Store\Model\ScopeInterface;

/**
 * Class Logger
 */
class Logger
{

    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Logger Constructor.
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Write to log file.
     *
     * @param mixed $msg The message
     */
    public function write($msg)
    {
        // Get the debug config value
        $debug = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/debug',
            ScopeInterface::SCOPE_STORE
        );

        // Get the file logging config value
        $fileLogging = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/file_logging',
            ScopeInterface::SCOPE_STORE
        );

        // Handle the file logging
        if ($debug && $fileLogging) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/checkoutcom_magento2.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($msg, 1));
        }
    }

    /**
     * Display the debug information on the front end.
     *
     * @param mixed $response The response
     */
    public function display($response)
    {
        // Get the debug config value
        $debug = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/debug',
            ScopeInterface::SCOPE_STORE
        );

        // Get the gateway response config value
        $gatewayResponses = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/gateway_responses',
            ScopeInterface::SCOPE_STORE
        );

        if ($debug && $gatewayResponses) {
            $output = print_r($response, 1);
            $this->messageManager->addComplexSuccessMessage(
                'ckoMessages',
                ['output' => $output]
            );
        }
    }
}
