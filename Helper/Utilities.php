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

/**
 * Class Utilities
 */
class Utilities
{
    /**
     * Convert a date string to ISO8601 format
     *
     * @param $timestamp
     *
     * @return false|string
     */
    public function formatDate($timestamp)
    {
        return gmdate("Y-m-d\TH:i:s\Z", $timestamp);
    }

    /**
     * Format an amount to 2 decimals
     *
     * @param $amount
     *
     * @return float|int
     */
    public function formatDecimals($amount)
    {
        return round($amount * 100) / 100;
    }

    /**
     * Convert an object to array
     *
     * @param $object
     *
     * @return mixed
     */
    public function objectToArray($object)
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Get the gateway payment information from an order
     *
     * @param $order
     *
     * @return mixed|null
     */
    public function getPaymentData($order)
    {
        $paymentData = $order->getPayment()
            ->getMethodInstance()
            ->getInfoInstance()
            ->getData();

        return $paymentData['additional_information']['transaction_info'] ?? null;
    }

    /**
     * Add the gateway payment information to an order
     *
     * @param      $order
     * @param      $data
     * @param null $source
     *
     * @return mixed
     */
    public function setPaymentData($order, $data, $source = null)
    {
        // Get the payment info instance
        $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();

        // Add the transaction info for order save after
        $paymentInfo->setAdditionalInformation(
            'transaction_info',
            array_intersect_key((array)$data, array_flip(['id']))
        );

        if (isset($source)) {
            if ($source['methodId'] === 'checkoutcom_apm') {
                // Add apm to payment information
                $paymentInfo->setAdditionalInformation(
                    'method_id',
                    $source['source']
                );
            } elseif ($source['methodId'] === 'checkoutcom_vault') {
                // Add vault public hash to payment information
                $paymentInfo->setAdditionalInformation(
                    'public_hash',
                    $source['publicHash']
                );
            }
        }

        return $order;
    }
}
