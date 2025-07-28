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

use CheckoutCom\Magento2\Logger\ResponseHandler;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Logger
 */
class Logger
{
    private ManagerInterface $messageManager;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private ResponseHandler $responseLogger;

    public function __construct(
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResponseHandler $responseLogger
    ) {
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->responseLogger = $responseLogger;
    }

    /**
     * Write to log file.
     *
     * @param mixed $msg The message
     *
     * @return void
     */
    public function write(mixed $msg): void
    {
        // Get the debug config value
        $debug = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/debug',
            ScopeInterface::SCOPE_WEBSITE
        );

        // Get the file logging config value
        $fileLogging = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/file_logging',
            ScopeInterface::SCOPE_WEBSITE
        );

        // Handle the file logging
        if ($debug && $fileLogging) {
            $message = is_array($msg) ? $msg : [$msg];
            $this->logger->debug('Checkout Logging: ', $message);
        }
    }

    /**
     * Write gateway responses in dynamic log files if configuration is enabled.
     *
     * @param mixed $response The response
     *
     * @return void
     */
    public function display(mixed $response): void
    {
        // Get the debug config value
        $debug = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/debug',
            ScopeInterface::SCOPE_WEBSITE
        );

        // Get the gateway response config value
        $gatewayResponses = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/gateway_responses',
            ScopeInterface::SCOPE_WEBSITE
        );

        if ($debug && $gatewayResponses) {
            $output = json_encode($response);
            $this->responseLogger->log($output);
        }
    }

    /**
     * Write additional debug logging
     *
     * @param mixed $msg The message
     * @param $type
     *
     * @return void
     */
    public function additional($msg, $type): void
    {
        $enabledLogging = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/additional_logging_enabled',
            ScopeInterface::SCOPE_WEBSITE
        );

        $loggingOptions = explode(
            ',',
            $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/additional_logging',
                ScopeInterface::SCOPE_WEBSITE
            ) ?? ''
        );

        if ($enabledLogging && in_array($type, $loggingOptions)) {
            $this->write($msg);
        }
    }
}
