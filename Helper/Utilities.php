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
     * Utilities constructor.
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->urlInterface = $urlInterface;
        $this->customerSession = $customerSession;
    }

    /**
     * Convert a date string to ISO8601 format.
     */
    public function formatDate($timestamp)
    {
        return gmdate("Y-m-d\TH:i:s\Z", $timestamp);
    }

    /**
     * Format an amount to 2 demicals.
     */
    public function formatDecimals($amount)
    {
        return ceil($amount * 100) / 100;
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
        $paymentData = $order->getPayment()
            ->getMethodInstance()
            ->getInfoInstance()
            ->getData();

        return $paymentData['additional_information']['transaction_info'];
    }

    /**
     * Add the gateway payment information to an order
     */
    public function setPaymentData($order, $data)
    {
        // Get the payment info instance
        $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();

        // Add the transaction info for order save after
        $paymentInfo->setAdditionalInformation(
            'transaction_info',
            (array) $data
        );

        return $order;
    }
}
