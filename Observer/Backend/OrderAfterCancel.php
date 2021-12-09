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

namespace CheckoutCom\Magento2\Observer\Backend;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;

/**
 * Class OrderAfterCancel.
 */
class OrderAfterCancel implements ObserverInterface
{
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    public $backendAuthSession;
    /**
     * @var RequestInterface
     */
    public $request;
    /**
     * @var Config
     */
    public $config;
    /**
     * @var OrderManagementInterface
     */
    public $orderManagement;
    /**
     * @var Order
     */
    public $orderModel;
    /**
     * @var Array
     */
    public $params;

    /**
     * OrderAfterCancel constructor
     *
     * @param Session                  $backendAuthSession
     * @param RequestInterface         $request
     * @param Config                    $config
     * @param OrderManagementInterface $orderManagement
     * @param Order                    $orderModel
     */
    public function __construct(
        Session $backendAuthSession,
        RequestInterface $request,
        Config $config,
        OrderManagementInterface $orderManagement,
        Order $orderModel
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request            = $request;
        $this->config              = $config;
        $this->orderManagement    = $orderManagement;
        $this->orderModel         = $orderModel;
    }

    /**
     * Run the observer.
     */
    public function execute(Observer $observer)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $this->params = $this->request->getParams();
            $payment      = $observer->getEvent()->getPayment();
            $order        = $payment->getOrder();
            $methodId     = $order->getPayment()->getMethodInstance()->getCode();

            if (in_array($methodId, $this->config->getMethodsList())) {
                $orderComments = $order->getStatusHistories();
                $orderComment  = array_pop($orderComments);

                $orderComment->setData('status', 'canceled')->save();
            }

            return $this;
        }
    }
}
