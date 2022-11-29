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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class Utilities
 */
class Utilities
{
    /**
     * @var Json
     */
    protected $json;

    public function __construct(
        Json $json
    ) {
        $this->json = $json;
    }

    /**
     * Convert a date string to ISO8601 format
     *
     * @param $timestamp
     *
     * @return string
     */
    public function formatDate($timestamp): string
    {
        return gmdate("Y-m-d\TH:i:s\Z", (int)$timestamp);
    }

    /**
     * Format an amount to 2 decimals
     *
     * @param float|int $amount
     *
     * @return float
     */
    public function formatDecimals($amount): float
    {
        return round($amount * 100) / 100;
    }

    /**
     * Convert an object to array
     *
     * @param $object
     *
     * @return array
     */
    public function objectToArray($object): array
    {
        return $this->json->unserialize($this->json->serialize($object));
    }

    /**
     * Get the gateway payment information from an order
     *
     * @param OrderInterface $order
     * @param string $data
     *
     * @return string[]|null
     * @throws LocalizedException
     */
    public function getPaymentData(OrderInterface $order, string $data = 'transaction_info'): ?array
    {
        $paymentData = $order->getPayment()
            ->getMethodInstance()
            ->getInfoInstance()
            ->getData();

        return $paymentData['additional_information'][$data] ?? null;
    }

    /**
     * Add the gateway payment information to an order
     *
     * @param OrderInterface $order
     * @param array $data
     * @param array|null $source
     *
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function setPaymentData(OrderInterface $order, array $data, array $source = null): OrderInterface
    {
        // Get the payment info instance
        $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();

        // Add the transaction info for order save after
        $paymentInfo->setAdditionalInformation(
            'transaction_info',
            array_intersect_key($data, array_flip(['id']))
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
