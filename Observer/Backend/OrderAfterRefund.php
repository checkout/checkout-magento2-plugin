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

/**
 * Class OrderAfterRefund
 */
class OrderAfterRefund implements ObserverInterface
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
     * OrderAfterRefund constructor
     *
     * @param Session          $backendAuthSession
     * @param Config           $config
     */
    public function __construct(
        Session $backendAuthSession,
        Config $config
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->config             = $config;
    }

    /**
     * Run the observer
     *
     * @param Observer $observer
     *
     * @return $this|null
     */
    public function execute(Observer $observer): ?OrderAfterRefund
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $payment  = $observer->getEvent()->getPayment();
            $order    = $payment->getOrder();
            $methodId = $order->getPayment()->getMethodInstance()->getCode();

            // Check if payment method is checkout.com
            if (in_array($methodId, $this->config->getMethodsList())) {
                $creditmemo = $observer->getEvent()->getCreditmemo();

                $status = ($order->getStatus() === 'closed' || $order->getStatus() === 'complete') ? $order->getStatus(
                ) : $this->config->getValue('order_status_refunded');

                // Update the order status
                $order->setStatus($status);
            }

            return $this;
        }

        return null;
    }
}
