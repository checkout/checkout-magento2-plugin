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
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderInterface = $orderInterface;
        $this->quoteHandler = $quoteHandler;
        $this->orderRepository = $orderRepository;
        $this->searchBuilder = $searchBuilder;
        $this->config = $config;
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
        
        // Place order from quote
        $quote = $this->quoteHandler->getQuote();
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