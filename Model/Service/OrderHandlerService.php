<?php

namespace CheckoutCom\Magento2\Model\Service;

class OrderHandlerService
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderInterface = $orderInterface;
        $this->quoteHandler = $quoteHandler;
        $this->config = $config;
    }

    /**
     * Places an order if not already created
     */
    public function placeOrder($methodId, $reservedIncrementId = null)
    {
        // Process transaction
        
        // Place order from quote
        $quote = $this->quoteHandler->findQuote();
        if ($quote) {

        }

    }

    /**
     * Tasks after place order
     */
    public function afterPlaceOrder($quote, $order)
    {
        // Prepare session quote info for redirection after payment
        $this->checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();
        // Prepare session order info for redirection after payment
        $this->checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }
}