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

namespace CheckoutCom\Magento2\Observer\Api;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Class OrderCreditMemoSaveAfter
 */
class OrderCreditMemoSaveAfter implements ObserverInterface
{
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;

    /**
     * OrderCreditMemoSaveAfter constructor
     *
     * @param Config                   $config
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Config $config,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->config             = $config;
        $this->orderRepository    = $orderRepository;
    }

    /**
     * Run the observer
     *
     * @param Observer $observer
     *
     * @return OrderCreditMemoSaveAfter
     * @throws LocalizedException
     */
    public function execute(Observer $observer): OrderCreditMemoSaveAfter
    {
        /** @var Creditmemo $creditMemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order      = $creditMemo->getOrder();
        $methodId   = $order->getPayment()->getMethodInstance()->getCode();

        // Check if payment method is checkout.com
        if (in_array($methodId, $this->config->getMethodsList())) {
            $status = ($order->getStatus() === 'closed' || $order->getStatus() === 'complete') ? $order->getStatus(
            ) : $this->config->getValue('order_status_refunded');

            // Update the order status
            $order->setStatus($status);

            // Get the latest order status comment
            $orderComments = $order->getStatusHistories();
            $orderComment  = array_pop($orderComments);

            // Update the order history comment status
            $this->orderRepository->save($order);
        }

        return $this;
    }
}
