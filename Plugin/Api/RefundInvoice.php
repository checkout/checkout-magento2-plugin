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

namespace CheckoutCom\Magento2\Plugin\Api;

use Magento\Sales\Model\Order;

/**
 * Class RefundInvoice.
 */
class RefundInvoice
{
    /**
     * @var MethodHandlerService
     */
    public $methodHandler;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->methodHandler = $methodHandler;
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->config = $config;
    }

    /**
     * Refund the order online.
     */
    public function aroundRefund(
        \Magento\Sales\Model\Order\RefundAdapter\Interceptor $subject,
        \Closure $proceed,
        \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo,
        \Magento\Sales\Api\Data\OrderInterface $order,
        $isOnline
    ) {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        // Get the method and method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();
        $method = $this->methodHandler->get($methodId);

        // Check if payment method is checkout.com
        if (in_array($methodId, $this->config->getMethodsList()) && $isOnline) {
            // Get the payment and amount to refund
            $payment = $order->getPayment();
            $amount = $creditmemo->getBaseGrandTotal();

            if (!$method->canRefund()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund
            $response = $api->refundOrder(
                $payment,
                $amount,
                true
            );

            if (!$api->isValidResponse($response)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The refund request could not be processed.')
                );
            }

            if ($this->statusNeedsCorrection($order)) {
                $order->setStatus($this->config->getValue('order_status_refunded'));
            }
            
            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
            $payment->save();
            
            $result = $proceed($creditmemo, $order, $isOnline);
        } else {
            $result = $proceed($creditmemo, $order, $isOnline);
        }

        return $result;
    }

    public function statusNeedsCorrection($order)
    {
        $currentState = $order->getState();
        $currentStatus = $order->getStatus();
        $desiredStatus = $this->config->getValue('order_status_refunded');
        
        return $currentState == Order::STATE_PROCESSING
            && $currentStatus !== $desiredStatus
            && $currentStatus !== 'closed';
    }
}
