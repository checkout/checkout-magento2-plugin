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
 * Class OrderAfterShipment.
 */
class OrderAfterShipment implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Array
     */
    public $params;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \Magento\Framework\App\RequestInterface $request,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->orderHandler = $orderHandler;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * Run the observer.
     */
    public function execute(Observer $observer)
    {
        // Get the request parameters
        $this->params = $this->request->getParams();
        
        if ($this->needsShippingLogic()) {
            // Get order and method id
            $order = $observer->getEvent()->getOrder();
            $methodId = $order->getPayment()->getMethodInstance()->getCode();

            // Check if payment method is checkout.com
            if (in_array($methodId, $this->config->getMethodsList())) {
                if ($this->statusNeedsCorrection($order)) {
                    // Update the order status
                    $order->setStatus($this->config->getValue('order_status_authorized'));
                    $order->save();
                }
            }

            return $this;
        }
    }

    /**
     * Checks if the shipping logic should be triggered.
     */
    public function needsShippingLogic()
    {
        return $this->backendAuthSession->isLoggedIn()
            && isset($this->params['shipment']);
    }

    /**
     * Checks if the status needs to be updated based on
     * admin configuration.
     */
    public function statusNeedsCorrection($order)
    {
        $currentStatus = $order->getStatus();
        $desiredStatus = $this->config->getValue('order_status_authorized');

        return $currentStatus !== $desiredStatus 
            && $currentStatus !== 'complete';
    }
}
