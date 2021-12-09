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

namespace CheckoutCom\Magento2\Observer\Backend;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class OrderAfterRefund.
 */
class OrderAfterRefund implements ObserverInterface
{
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    public $backendAuthSession;
    /**
     * $request field
     *
     * @var RequestInterface $request
     */
    public $request;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $params field
     *
     * @var array $params
     */
    public $params;

    /**
     * OrderAfterRefund constructor
     *
     * @param Session          $backendAuthSession
     * @param RequestInterface $request
     * @param Config            $config
     */
    public function __construct(
        Session $backendAuthSession,
        RequestInterface $request,
        Config $config
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request            = $request;
        $this->config              = $config;
    }

    /**
     * Run the observer
     *
     * @param Observer $observer
     *
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the request parameters
            $this->params = $this->request->getParams();

            $payment  = $observer->getEvent()->getPayment();
            $order    = $payment->getOrder();
            $methodId = $order->getPayment()->getMethodInstance()->getCode();

            // Check if payment method is checkout.com
            if (in_array($methodId, $this->config->getMethodsList())) {
                $creditmemo = $observer->getEvent()->getCreditmemo();

                $status = ($order->getStatus() == 'closed' || $order->getStatus() == 'complete') ? $order->getStatus(
                ) : $this->config->getValue('order_status_refunded');

                // Update the order status
                $order->setStatus($status);
            }

            return $this;
        }
    }
}
