<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class OrderHandlerService.
 */
class OrderHandlerService
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Session
     */
    protected $customerSession;

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
     * @var TransactionHandlerService
     */
    protected $transactionHandler;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * @var Array
     */
    protected $paymentData;

    /**
     * OrderHandler constructor
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderInterface  = $orderInterface;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->searchBuilder = $searchBuilder;
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
        $this->transactionHandler = $transactionHandler;
        $this->logger = $logger;
    }

    /**
     * Set the payment method id
     */
    public function setMethodId($methodId)
    {
        $this->methodId = $methodId;
        return $this;
    }

    /**
     * Places an order if not already created
     */
    public function handleOrder($paymentData, $reservedIncrementId = '', $isWebhook = false)
    {
        if ($this->methodId) {
            try {
                // Check if the order exists
                $order = $this->getOrder(
                    ['increment_id' => $reservedIncrementId]
                );

                // Create the order
                if (!$this->isOrder($order)) {
                    // Prepare the quote
                    $quote = $this->quoteHandler->prepareQuote(
                        $this->methodId,
                        ['reserved_order_id' => $reservedIncrementId],
                        $isWebhook
                    );

                    // Process the quote
                    if ($quote) {
                        // Create the order
                        $order = $this->quoteManagement->submit($quote);
                    }

                    // Return the saved order
                    $order = $this->orderRepository->save($order);
                }

                // Perform after place order tasks
                if (!$isWebhook) {
                    $order = $this->afterPlaceOrder($quote, $order);
                }

                return $order;
            } catch (\Exception $e) {
                $this->logger->write($e->getMessage());
                return null;
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('A payment method ID is required to place an order.')
            );
        }
    }

    /**
     * Checks if an order exists and is valid
     */
    public function isOrder($order)
    {
        try {
            return $order
            && is_object($order)
            && method_exists($order, 'getId')
            && $order->getId() > 0;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Load an order
     */
    public function getOrder($fields)
    {
        try {
            return $this->findOrderByFields($fields);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Find an order by fields
     */
    public function findOrderByFields($fields)
    {
        try {
            // Add each field as filter
            foreach ($fields as $key => $value) {
                $this->searchBuilder->addFilter(
                    $key,
                    $value
                );
            }

            // Create the search instance
            $search = $this->searchBuilder->create();

            // Get the resulting order
            $order = $this->orderRepository
                ->getList($search)
                ->getFirstItem();

            return $order;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Tasks after place order
     */
    public function afterPlaceOrder($quote, $order)
    {
        try {
            // Prepare session quote info for redirection after payment
            $this->checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();

            // Prepare session order info for redirection after payment
            $this->checkoutSession->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            return $order;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
