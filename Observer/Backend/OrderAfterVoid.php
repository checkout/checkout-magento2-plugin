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

namespace CheckoutCom\Magento2\Observer\Backend;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

/**
 * Class OrderAfterVoid
 */
class OrderAfterVoid implements ObserverInterface
{
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    private $backendAuthSession;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $orderManagement field
     *
     * @var OrderManagementInterface $orderManagement
     */
    private $orderManagement;
    /**
     * $orderStatusHistoryRepository field
     *
     * @var OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    private $orderStatusHistoryRepository;

    /**
     * OrderAfterVoid constructor
     *
     * @param Session                               $backendAuthSession
     * @param Config                                $config
     * @param OrderManagementInterface              $orderManagement
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    public function __construct(
        Session $backendAuthSession,
        Config $config,
        OrderManagementInterface $orderManagement,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->backendAuthSession           = $backendAuthSession;
        $this->config                       = $config;
        $this->orderManagement              = $orderManagement;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * Run the observer
     *
     * @param Observer $observer
     *
     * @return OrderAfterVoid|void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): ?OrderAfterVoid
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            /** @var Payment $payment */
            $payment  = $observer->getEvent()->getPayment();
            $order    = $payment->getOrder();
            $methodId = $order->getPayment()->getMethodInstance()->getCode();

            // Check if payment method is checkout.com
            if (in_array($methodId, $this->config->getMethodsList())) {
                // Update the order status
                $order->setStatus($this->config->getValue('order_status_voided'));

                // Get the latest order status comment
                $orderComments = $order->getStatusHistories();
                $orderComment  = array_pop($orderComments);
                $comment       = __('The voided amount is %1.', $order->formatPriceTxt($order->getGrandTotal()));

                // Update the order history comment
                $orderComment->setData('status', $this->config->getValue('order_status_voided'));
                $orderComment->setData('comment', $comment);
                $this->orderStatusHistoryRepository->save($orderComment);

                if ($this->config->getValue('order_status_voided') === 'canceled') {
                    // Cancel the order if void order status has been set to canceled
                    $this->orderManagement->cancel($order->getId());
                } else {
                    // Order state needs to be set to new so that offline transactions update the order status
                    $order->setState(Order::STATE_NEW);
                }
            }

            return $this;
        }
    }
}
