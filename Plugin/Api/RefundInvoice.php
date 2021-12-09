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

namespace CheckoutCom\Magento2\Plugin\Api;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use Closure;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\RefundAdapter\Interceptor;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class RefundInvoice.
 */
class RefundInvoice
{
    /**
     * $methodHandler field
     *
     * @var MethodHandlerService $methodHandler
     */
    public $methodHandler;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    public $storeManager;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    public $apiHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;

    /**
     * RefundInvoice constructor
     *
     * @param MethodHandlerService  $methodHandler
     * @param StoreManagerInterface $storeManager
     * @param ApiHandlerService     $apiHandler
     * @param Config                 $config
     */
    public function __construct(
        MethodHandlerService $methodHandler,
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        Config $config
    ) {
        $this->methodHandler = $methodHandler;
        $this->storeManager  = $storeManager;
        $this->apiHandler    = $apiHandler;
        $this->config         = $config;
    }

    /**
     * Refund the order online
     *
     * @param Interceptor         $subject
     * @param Closure             $proceed
     * @param CreditmemoInterface $creditMemo
     * @param OrderInterface      $order
     * @param false               $isOnline
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundRefund(
        Interceptor $subject,
        Closure $proceed,
        CreditmemoInterface $creditMemo,
        OrderInterface $order,
        $isOnline = false
    ) {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        // Get the method and method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Check if payment method is checkout.com
        if (in_array($methodId, $this->config->getMethodsList()) && $isOnline) {
            // Get the payment and amount to refund
            $payment = $order->getPayment();
            $amount  = $creditMemo->getBaseGrandTotal();
            $method  = $this->methodHandler->get($methodId);

            if (!$method->canRefund()) {
                throw new LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund
            $response = $api->refundOrder($payment, $amount);

            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The refund request could not be processed.')
                );
            }

            if ($this->statusNeedsCorrection($order)) {
                $order->setStatus($this->config->getValue('order_status_refunded'));
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
            $payment->save();

            $result = $proceed($creditMemo, $order, $isOnline);
        } else {
            $result = $proceed($creditMemo, $order, $isOnline);
        }

        return $result;
    }

    /**
     * Description statusNeedsCorrection function
     *
     * @param $order
     *
     * @return bool
     */
    public function statusNeedsCorrection($order)
    {
        $currentState  = $order->getState();
        $currentStatus = $order->getStatus();
        $desiredStatus = $this->config->getValue('order_status_refunded');

        return $currentState == Order::STATE_PROCESSING && $currentStatus !== $desiredStatus && $currentStatus !== 'closed';
    }
}
