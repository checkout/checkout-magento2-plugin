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

use Magento\Framework\Event\Observer;

/**
 * Class OrderAfterVoid.
 */
class OrderAfterVoid implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
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
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\RequestInterface $request,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Sales\Model\Order $orderModel
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->config = $config;
        $this->orderManagement = $orderManagement;
        $this->orderModel = $orderModel;
    }

    /**
     * Run the observer.
     */
    public function execute(Observer $observer)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the request parameters
            $this->params = $this->request->getParams();

            $payment = $observer->getEvent()->getPayment();
            $order = $payment->getOrder();
            $methodId = $order->getPayment()->getMethodInstance()->getCode();

            // Check if payment method is checkout.com
            if (in_array($methodId, $this->config->getMethodsList())) {
                // Update the order status
                $order->setStatus($this->config->getValue('order_status_voided'));

                // Get the latest order status comment
                $orderComments = $order->getStatusHistories();
                $orderComment = array_pop($orderComments);
                $comment = __('The voided amount is %1.', $order->formatPriceTxt($order->getGrandTotal()));

                // Update the order history comment
                $orderComment->setData('status', $this->config->getValue('order_status_voided'))->save();
                $orderComment->setData('comment', $comment)->save();

                if ($this->config->getValue('order_status_voided') == 'canceled') {
                    // Cancel the order if void order status has been set to canceled
                    $this->orderManagement->cancel($order->getId());
                } else {
                    // Order state needs to be set to new so that offline transactions update the order status
                    $order->setState($this->orderModel::STATE_NEW);
                }
            }

            return $this;
        }
    }

}
