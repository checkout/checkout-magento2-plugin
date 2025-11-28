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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Request\Base;

use CheckoutCom\Magento2\Model\Formatter\DateFormatter;
use CheckoutCom\Magento2\Model\Request\Additionnals\Summary;
use CheckoutCom\Magento2\Model\Request\Additionnals\SummaryFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class SummaryElement
{
    protected DateFormatter $dateFormatter;
    protected JsonSerializer $serializer;
    protected OrderCollectionFactory $orderCollectionFactory;
    protected SummaryFactory $modelFactory;

    public function __construct(
        DateFormatter $dateFormatter,
        JsonSerializer $serializer,
        OrderCollectionFactory $orderCollectionFactory,
        SummaryFactory $modelFactory
    ) {
        $this->dateFormatter = $dateFormatter;
        $this->modelFactory = $modelFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->serializer = $serializer;
    }

    public function get(CustomerInterface $customer, string $currency): Summary
    {
        /** @var Summary $model */
        $model = $this->modelFactory->create();

        $customerOrders = $customer->getId() ? $this->getCustomerOrders((int) $customer->getId(), $currency) : [];

        $orderCount = count($customerOrders);
        $isReturning = $orderCount > 0;

        if ($customer->getCreatedAt()) {
            $model->registration_date = $this->dateFormatter->getFormattedDate($customer->getCreatedAt());
        }

        $model->total_order_count = $orderCount;
        $model->is_premium_customer = false;
        $model->is_returning_customer = $isReturning;

        if (!$isReturning) {
            return $model;
        }

        $lastOrder = next($customerOrders);
        $firstOrder = end($customerOrders);

        if ($firstOrder) {
            $model->first_transaction_date = $this->dateFormatter->getFormattedDate($firstOrder->getCreatedAt());
        }

        if ($lastOrder) {
            $model->last_payment_date = $this->dateFormatter->getFormattedDate($lastOrder->getCreatedAt());
            $model->last_payment_amount = (float) $lastOrder->getGrandTotal();
        }
        
        $model->lifetime_value = $this->getLifeTimeValue($customerOrders);

        return $model;
    }

    private function getCustomerOrders(int $customerId, string $currency): array
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('base_currency_code', $currency)
            ->setOrder('created_at', 'DESC');

            $paymentTable = $collection->getResource()->getTable('sales_order_payment');

            $collection->getSelect()->join(
                ['sop' => $paymentTable],
                'main_table.entity_id = sop.parent_id',
                'additional_information'
            );

        return $collection->getItems();
    }

    private function getLifeTimeValue($orders): float
    {
        $eligibleOrder = $this->filterOrder($orders);

        return array_reduce($eligibleOrder, function($accumulator, $order) {
            return $accumulator + (float) $order->getGrandTotal() - (float) $order->getTotalRefunded();
        }, 0);
    }

    private function filterOrder($orders): array
    {
        return array_filter($orders, function ($order) {
            if ($order->isCanceled()) {
                return false;
            }

            if ($order->getState() === Order::STATE_CLOSED) {
                return false;
            }

            $paymentData = $order->getAdditionalInformation();

            if (empty($paymentData)) {
                return false;
            }

            $additionnal_data = $this->serializer->unserialize($paymentData);

            if (empty($additionnal_data)) {
                return true;
            }

            $flowMethod = $additionnal_data['flow_method_id'] ?? null;

            return $flowMethod !== 'checkoutcom_tamara';
        });
    }
}
