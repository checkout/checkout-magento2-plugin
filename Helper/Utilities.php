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

/**
 * Class Utilities
 */
class Utilities
{
    /**
     * @var UrlInterface
     */
    public $urlInterface;

    /**
     * @var Dir
     */
    public $moduleDirReader;

    /**
     * @var File
     */
    public $fileDriver;

    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * Utilities constructor.
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Customer\Model\Session $customerSession,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->urlInterface = $urlInterface;
        $this->moduleDirReader = $moduleDirReader;
        $this->fileDriver = $fileDriver;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Convert a date string to ISO8601 format.
     */
    public function formatDate($timestamp)
    {
        return gmdate("Y-m-d\TH:i:s\Z", $timestamp);
    }

    /**
     * Convert an object to array.
     */
    public function objectToArray($object)
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Get the gateway payment information from an order
     */
    public function getPaymentData($order)
    {
        try {
            $paymentData = $order->getPayment()
                ->getMethodInstance()
                ->getInfoInstance()
                ->getData();

            return $paymentData['additional_information']['transaction_info'];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return [];
        }
    }

    /**
     * Add the gateway payment information to an order
     */
    public function setPaymentData($order, $data)
    {
        try {
            // Get the payment info instance
            $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();

            // Add the transaction info for order save after
            $paymentInfo->setAdditionalInformation(
                'transaction_info',
                (array) $data
            );

            return $order;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Get the module version
     */
    public function getModuleVersion($prefix = '')
    {
        try {
            // Build the composer file path
            $filePath = $this->moduleDirReader->getModuleDir(
                '',
                'CheckoutCom_Magento2'
            ) . '/composer.json';

            // Get the composer file content
            $fileContent = json_decode(
                $this->fileDriver->fileGetContents($filePath)
            );

            return $prefix . $fileContent->version;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '...';
        }
    }
}
