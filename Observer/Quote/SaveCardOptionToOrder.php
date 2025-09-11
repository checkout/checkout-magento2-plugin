<?php

namespace CheckoutCom\Magento2\Observer\Quote;

use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveCardOptionToOrder implements ObserverInterface
{
    /**
     * Transfert the saved card option from quote to order
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        if (!$quote || !$order) {
            return;
        }

        $order->setData(FlowGeneralSettings::SALES_ATTRIBUTE_SHOULD_SAVE_CARD, (int)$quote->getData(FlowGeneralSettings::SALES_ATTRIBUTE_SHOULD_SAVE_CARD));
    }
}
