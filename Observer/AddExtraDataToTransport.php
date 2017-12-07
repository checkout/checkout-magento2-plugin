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

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Api\Data\OrderInterface;

class AddExtraDataToTransport implements ObserverInterface {

    protected $scopeConfig;
    protected $checkoutSession;
    protected $orderInterface;

    public function __construct(ScopeConfigInterface $scopeConfig, Session $checkoutSession, OrderInterface $orderInterface)
    {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->orderInterface = $orderInterface;
    }

    public function execute(Observer $observer)
    {
        // Get the current payment method used
        $paymentMethod = $this->checkoutSession->getQuote()->getPayment()->getMethod();

        if ($paymentMethod == 'checkout_com' || $paymentMethod == 'checkout_com_cc_vault') {
 
            // Get the email content
            $transport = $observer->getEvent()->getTransport();

            // Override the payment information block
            $transport['payment_html'] = $this->scopeConfig->getValue('payment/checkout_com/title');
        }
    }
}
