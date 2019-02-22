<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class AfterPlaceOrderObserver implements ObserverInterface {

    protected $request;
    protected $session;
	protected $gatewayConfig;

    public function __construct( RequestInterface $request, Session $session, GatewayConfig $gatewayConfig)
    {
        $this->request = $request;
        $this->session = $session;
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * Handles the observer for order placement.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) {
        // Get the order
        $order = $observer->getEvent()->getOrder();

        // Get 3D Secure setting
        $is3ds = (int) $this->gatewayConfig->isVerify3DSecure();

        // Force the order status to what is in config for Frames only
        if ($is3ds == 1 && $order->getPayment()->getMethod() == 'checkout_com') {
            // Update order status
            $order->setStatus($this->gatewayConfig->getNewOrderStatus());
        }

        return $this;
    }
}
