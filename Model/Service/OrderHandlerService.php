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
     * @var QuoteManagement
     */
     protected $quoteManagement;

    /**
     * @var OrderRepositoryInterface
     */
     protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
     protected $searchBuilder;
     
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderInterface = $orderInterface;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->searchBuilder = $searchBuilder;
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
    }

    /**
     * Load an order by field
     */
    public function getOrder($fields = [])
    {
        try {
            if (count($fields) > 0) {
                // Add each field as filter
                foreach ($fields as $key => $value) {
                    $this->searchBuilder->addFilter(
                        $key,
                        $value
                    );
                }
                
                // Create the search instance
                $search = $this->searchBuilder->create();

                // Get the resultin order
                $order = $this->orderRepository
                    ->getList($search)
                    ->getFirstItem();

                return $order;
            }
            else {
                // Try to find and order id in session
                $orderId = $this->checkoutSession->getLastOrderId();

                // Load the order from id
                $order = $this->orderRepository->get($orderId);

                return $order;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Places an order if not already created
     */
    public function placeOrder($methodId, $reservedIncrementId = null)
    {
        // Prepare the parameters
        $order = null;

        // Check if the order exists
        if ($reservedIncrementId) {
            $order = $this->getOrder([
                'reserved_order_id' => $reservedIncrementId
            ]);
        }

        // Create the order
        if (!$order && $reservedIncrementId) {
            $quote = $this->quoteHandler->getQuote(
                ['reserved_order_id' => $reservedIncrementId]
            );

            if ($quote &&  $quote->getId() > 0) {
                // Prepare the inventory
                $quote->setInventoryProcessed(false);

                // Check for guest user quote
                if ($this->customerSession->isLoggedIn() === false) {
                    $quote = $this->prepareGuestQuote($quote);
                }

                // Set the payment information
                $payment = $quote->getPayment();
                $payment->setMethod($methodId);
                $payment->save();

                // Create the order
                $order = $this->quoteManagement->submit($quote);
            }
        }

        return $order;
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