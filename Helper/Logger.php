<?php

/**
 * Checkout.com
 * Authorised and regulated as an electronic money institution
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

class Logger {

    /**
     * @var ManagerInterface
     */
	protected $messageManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

    /**
     * Constructor.
     *
     * @param      \Magento\Framework\Message\ManagerInterface            $messageManager  The message manager
     * @param      \CheckoutCom\Magento2\Gateway\Config\Config            $config          The configuration
     * @param      \CheckoutCom\Magento2\Model\Service\ApiHandlerService  $apiHandler      The api handler
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler
    ) {
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
	}

    /**
     * Write to log file.
     *
     * @param      mixed  $msg    The message
     */
	public function write($msg) {
        if ($this->config->getValue('debug') && $this->config->getValue('file_logging')) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/checkoutcom_magento2.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($msg, 1));
        }
	}

    /**
     * Display the debug information on the front end.
     *
     * @param      mixed  $response  The response
     */
	public function display($response) {
        if ($this->config->getValue('debug') && $this->config->getValue('gateway_responses')) {
            $paymentId = $this->apiHandler->getPaymentId();
            $this->ggetPaymentDetails($paymentId);
            $this->messageManager->addSuccessMessage($msg);
        }
	}
}