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
 * Class OrderAfterCancel
 */
class OrderAfterCancel implements ObserverInterface
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
     * $orderStatusHistoryRepository field
     *
     * @var OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    private $orderStatusHistoryRepository;

    /**
     * OrderAfterCancel constructor
     *
     * @param Session                               $backendAuthSession
     * @param Config                                $config
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    public function __construct(
        Session $backendAuthSession,
        Config $config,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->backendAuthSession           = $backendAuthSession;
        $this->config                       = $config;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * Run the observer
     *
     * @param Observer $observer
     *
     * @return $this|void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): OrderAfterCancel
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            /** @var Payment $payment */
            $payment  = $observer->getEvent()->getPayment();
            $order    = $payment->getOrder();
            $methodId = $order->getPayment()->getMethodInstance()->getCode();

            if (in_array($methodId, $this->config->getMethodsList())) {
                $orderComments = $order->getStatusHistories();
                $orderComment  = array_pop($orderComments);
                $orderComment->setData('status', 'canceled');

                $this->orderStatusHistoryRepository->save($orderComment);
            }

            return $this;
        }
    }
}
