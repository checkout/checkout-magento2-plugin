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
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

class AddExtraDataToTransport implements ObserverInterface
{

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

    public function execute(Observer $observer)
    {
        // Get the current payment method used
        $transport = $observer->getEvent()->getTransport();

        // Get the payment method
        $paymentMethod = $transport->getOrder()->getPayment()->getMethod();

        // Test the current method used
        if (false
            || $paymentMethod == ConfigProvider::CODE
            || $paymentMethod == ConfigProvider::CC_VAULT_CODE
            || $paymentMethod == ConfigProvider::THREE_DS_CODE
        ) {
            // Override the payment information block
            $transport['payment_html'] = $this->scopeConfig->getValue(
                'payment/checkout_com/title',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $transport->getStore()
            );
        }
    }
}
