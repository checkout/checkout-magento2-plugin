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

class AddExtraDataToTransport implements ObserverInterface
{
    const XPATH_PAYMENT_TITLE = 'payment/checkout_com/title';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * AddExtraDataToTransport constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @event email_order_set_template_vars_before
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get the email sender transport
        /** @var \Magento\Framework\DataObject $transport */
        $transport = $observer->getEvent()->getTransport();

        // Get the current payment method used
        $paymentMethod = $transport->getOrder()->getPayment()->getMethod();

        if (in_array($paymentMethod, ['checkout_com', 'checkout_com_cc_vault'])) {
            // Override the payment information block
            $transport->setData('payment_html', $this->scopeConfig->getValue(
                self::XPATH_PAYMENT_TITLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $transport->getStore()
            ));
        }
    }
}
