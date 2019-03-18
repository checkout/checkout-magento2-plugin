<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Framework\Event\Observer;
use Magento\Backend\Model\Auth\Session as BackendAuthSession;
use Magento\Framework\App\Request\Http;
use CheckoutCom\Magento2\Model\Service\TokenChargeService;

class OrderSaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var BackendAuthSession
     */
    protected $backendAuthSession;

    /**
     * @var TokenChargeService
     */
    protected $tokenChargeService;

    /**
     * @var Http
     */
    protected $request;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        BackendAuthSession $backendAuthSession,
        TokenChargeService $tokenChargeService,
        Http $request
    ) {
        $this->backendAuthSession    = $backendAuthSession;
        $this->tokenChargeService    = $tokenChargeService;
        $this->request               = $request;

        // Get the request parameters
        $this->params = $this->request->getParams();
    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer)
    {
        // Get the order
        $order = $observer->getEvent()->getOrder();

        // Get the method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Process the payment
        if ($this->backendAuthSession->isLoggedIn() && isset($this->params['ckoCardToken']) && $methodId == 'checkout_com_admin_method') {

            // Send the charge request
            $result = $this->tokenChargeService->sendChargeRequest(
                $this->params['ckoCardToken'],
                $order,
                $disable3ds = true,
                $isQuote = false
            );
            
            // Save result in session of order save after
            $this->backendAuthSession->setCkoOrderPayment([
                $order->getIncrementId() => $result
            ]);
        }
      
        return $this;
    }
}
