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

namespace CheckoutCom\Magento2\Plugin\Backend;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;

/**
 * Class PaymentAfterCapture.
 */
class OrderAfterInvoice
{
    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * PaymentAfterVoid constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->webhookHandler = $webhookHandler;
        $this->config = $config;
    }

    public function aroundCheck(State $subject, callable $proceed, Order $order)
    {
        // Get the method ID
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Check if payment method is checkout.com
        if (in_array($methodId, $this->config->getMethodsList())) {
            if ($this->statusNeedsCorrection($order)) {
                $order->setStatus($this->config->getValue('order_status_captured'));
            }
        } else {
           return $proceed($order);
        }
    }

    public function statusNeedsCorrection($order)
    {
        $currentState = $order->getState();
        $currentStatus = $order->getStatus();
        $desiredStatus = $this->config->getValue('order_status_captured');
        $flaggedStatus = $this->config->getValue('order_status_flagged');

        return $currentState == Order::STATE_PROCESSING
                && $currentStatus !== $flaggedStatus
                && $currentStatus !== $desiredStatus
                && $currentStatus == 'processing';
    }
}
