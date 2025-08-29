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

namespace CheckoutCom\Magento2\Cron;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Methods\PayByLinkMethod;
use DateInterval;
use Exception;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class AutoCancelPendingPayByLinkOrders
{
    private Config $config;
    private CollectionFactory $orderCollectionFactory;
    private LoggerInterface $logger;
    private StoreManagerInterface $storeManager;
    private TimezoneInterface $timezone;
    private OrderManagementInterface $orderManagement;

    public function __construct(
        Config $config,
        CollectionFactory $orderCollectionFactory,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone,
        OrderManagementInterface $orderManagement
    ) {
        $this->config = $config;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
        $this->orderManagement = $orderManagement;
    }

    /**
     * Auto cancel orders for wich payment link is expired
     */
    public function execute(): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            $storeCode = $store->getCode();

            if (!$this->config->getValue('active', PayByLinkMethod::CODE, $storeCode, ScopeInterface::SCOPE_STORE) ||
                !$this->config->getValue('enable_order_auto_cancel', PayByLinkMethod::CODE, $storeCode, ScopeInterface::SCOPE_STORE)
            ) {
                continue;
            }
            $this->logger->info(sprintf('Checking for pay by links orders to cancel for store %s', $storeCode));
            $ordersCollection = $this->getStoreOrderCollection($store);
            $this->logger->info(sprintf('%s pay by links orders to cancel for store %s', $ordersCollection->getTotalCount(), $storeCode));
            foreach ($ordersCollection as $order) {
                try {
                    if (!$order->canCancel()) {
 throw new LocalizedException(__('The order %s cannot be cancelled.', $order->getIncrementId()));
                    } 
                        $cancelMsg = __('Auto cancel order %s because link is expired')->render();
                        $this->logger->info(
                            sprintf(
                                $cancelMsg,
                                $order->getIncrementId()
                            )
                        );
                        $order->addStatusHistoryComment($cancelMsg);
                        $this->orderManagement->cancel((int)$order->getId());
                } catch (Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Error while trying to auto cancel order %s : %s',
                            $order->getIncrementId(),
                            $e->getMessage()
                        )
                    );
                }
            }
        }
    }

    private function getStoreOrderCollection(StoreManagerInterface $store): OrderCollection
    {
        $expirationDelay = (int)$this->config->getValue('cancel_order_link_after', PayByLinkMethod::CODE, $store->getCode(), ScopeInterface::SCOPE_STORE);
        $minDate = $this->timezone->date()->sub(DateInterval::createFromDateString($expirationDelay . ' second'))->format(Mysql::TIMESTAMP_FORMAT);
        $ordersCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter(OrderInterface::STATUS, (string)$this->config->getValue('order_status_waiting_payment', PayByLinkMethod::CODE, $store->getCode(), ScopeInterface::SCOPE_STORE))
            ->addFieldToFilter(OrderInterface::STORE_ID, $store->getId())
            ->addFieldToFilter(OrderInterface::CREATED_AT, ['lteq' => $minDate]);
        $paymentTable = $ordersCollection->getResource()->getTable('sales_order_payment');

        return $ordersCollection->getSelect()->join(
            ['sop' => $paymentTable],
            'main_table.entity_id = sop.parent_id',
            ['method']
        )->where('sop.method = ?', PayByLinkMethod::CODE);
    }
}
