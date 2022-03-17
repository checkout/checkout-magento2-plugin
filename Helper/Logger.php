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

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Logger
 */
class Logger
{
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    private $messageManager;
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * $logger field
     *
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * Logger constructor
     *
     * @param ManagerInterface     $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface      $logger
     */
    public function __construct(
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->messageManager = $messageManager;
        $this->scopeConfig    = $scopeConfig;
        $this->logger         = $logger;
    }

    /**
     * Write to log file.
     *
     * @param mixed $msg The message
     *
     * @return void
     */
    public function write($msg): void
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
            $this->logger->debug('Checkout Logging: ', $msg);
        }
    }

    /**
     * Display the debug information on the front end.
     *
     * @param mixed $response The response
     *
     * @return void
     */
    public function display($response): void
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
            $output = json_encode($response);
            $this->messageManager->addComplexSuccessMessage('ckoMessages', ['output' => $output]);
        }
    }

    /**
     * Write additional debug logging
     *
     * @param mixed $msg The message
     *
     * @return void
     */
    public function additional($msg, $type): void
    {
        $enabledLogging = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/additional_logging_enabled',
            ScopeInterface::SCOPE_STORE
        );

        $loggingOptions = explode(
            ',',
            $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/additional_logging',
                ScopeInterface::SCOPE_STORE
            )
        );

        if ($enabledLogging && in_array($type, $loggingOptions)) {
            $this->write($msg);
        }
    }
}
