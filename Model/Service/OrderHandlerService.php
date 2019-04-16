<?php

namespace CheckoutCom\Magento2\Model\Service;

class OrderHandlerService
{
    protected $checkoutSession;
    protected $config;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
    	\CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
    	$this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * Places an order if not already created
     */
    /*
    public function placeOrder($data, $methodId)
    {
        // Get the fields
        $order = null;
        $fields = Connector::unpackData($data);
        // If a track id is available
        if (isset($fields[$this->config->base[Connector::KEY_ORDER_ID_FIELD]])) {
            // Check if the order exists
            $order = $this->orderInterface->loadByIncrementId(
                $fields[$this->config->base[Connector::KEY_ORDER_ID_FIELD]]
            );
            // Update the order
            if ((int) $order->getId() == 0) {
                $order = $this->createOrder($fields, $methodId);
                return $order;
            }
        }
        return $order;
    }
*/
    /**
     * Creates an order
     */

    /*
    public function createOrder($fields, $methodId)
    {
        try {
            // Find the quote
            $quote = $this->findQuote(
                $fields[$this->config->base[Connector::KEY_ORDER_ID_FIELD]]
            );
            // If there is a quote, create the order
            if ($quote->getId()) {
                // Prepare the inventory
                $quote->setInventoryProcessed(false);
                // Check for guest user quote
                if ($this->customerSession->isLoggedIn() === false) {
                    $quote = $this->prepareGuestQuote(
                        $quote,
                        $fields[$this->config->base[Connector::KEY_CUSTOMER_EMAIL_FIELD]]
                    );
                }
                // Set the payment information
                $payment = $quote->getPayment();
                $payment->setMethod($methodId);
                $payment->save();
                // Create the order
                $order = $this->quoteManagement->submit($quote);
                // Update order status
                $isCaptureImmediate = $this->config->params[$methodId]
                [Connector::KEY_CAPTURE_MODE] == Connector::KEY_CAPTURE_IMMEDIATE;
                if ($isCaptureImmediate) {
                    // Create the transaction
                    $transactionId = $this->transactionHandler->createTransaction(
                        $order,
                        $fields,
                        Transaction::TYPE_CAPTURE,
                        $methodId
                    );
                } else {
                    // Update order status
                    $order->setStatus(
                        $this->config->params[Core::moduleId()][Connector::KEY_ORDER_STATUS_AUTHORIZED]
                    );
                    // Create the transaction
                    $transactionId = $this->transactionHandler->createTransaction(
                        $order,
                        $fields,
                        Transaction::TYPE_AUTH,
                        $methodId
                    );
                }
                // Save the order
                $this->orderRepository->save($order);
                // Send the email
                $this->orderSender->send($order);
                
                return $order;
            }
        } catch (\Exception $e) {
            $this->watchdog->logError($e);
            return false;
        }
    }
    */

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