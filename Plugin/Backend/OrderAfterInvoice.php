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

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\CommandInterface as BaseCommandInterface;

/**
 * Class OrderAfterInvoice.
 */
class OrderAfterInvoice
{
    /**
     * @var Config
     */
    public $config;

    /**
     * OrderAfterInvoice constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Sets the correct order status for orders captured from the hub.
     *
     * @param BaseCommandInterface $subject
     * @param Closure $proceed
     * @param OrderPaymentInterface $payment
     * @param $amount
     * @param OrderInterface $order
     */
    public function aroundExecute(
        BaseCommandInterface $subject,
        \Closure $proceed,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        $result = $proceed($payment, $amount, $order);

        // Get the method ID
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Check if payment method is checkout.com
        if (in_array($methodId, $this->config->getMethodsList())) {
            if ($this->statusNeedsCorrection($order)) {
                if ($order->getIsVirtual()) {
                    $order->setStatus('complete');
                } else {
                    $order->setStatus($this->config->getValue('order_status_captured'));    
                }
            }

            // Changes order history comment to display currency
            $amount = $order->getInvoiceCollection()->getFirstItem()->getGrandTotal();
            $comment = __('The captured amount is %1.', $order->formatPriceTxt($amount));
            
            return $comment;
        }

        return $result;
    }

    /**
     * Check if the order status needs updating.
     *
     * @param $order
     * @return bool
     */
    public function statusNeedsCorrection($order)
    {
        $currentState = $order->getState();
        $currentStatus = $order->getStatus();
        $desiredStatus = $this->config->getValue('order_status_captured');
        $flaggedStatus = $this->config->getValue('order_status_flagged');

        return ($currentState == Order::STATE_PROCESSING
                && $currentStatus !== $flaggedStatus
                && $currentStatus !== $desiredStatus
                && $currentStatus == 'processing') || $order->getIsVirtual();
    }
}
