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

use Magento\Framework\Registry;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Class OrderHandlerService.
 */
class OrderHandlerService
{
    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var OrderInterface
     */
    public $orderInterface;

    /**
     * @var QuoteManagement
     */
    public $quoteManagement;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchBuilder;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var String
     */
    public $methodId;

    /**
     * @var Array
     */
    public $paymentData;

    /**
     * @var Registry
     */
    public $registry;

    /**
     * @var Order
     */
    public $orderModel;
    /**
     * OrderHandler constructor
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderInterface  = $orderInterface;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->orderModel = $orderModel;
        $this->registry = $registry;
        $this->searchBuilder = $searchBuilder;
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
        $this->storeManager = $storeManager;
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
    public function handleOrder($quote = null, $external = false)
    {
        if ($this->methodId) {
            // Prepare the quote
            $quote = $this->quoteHandler->prepareQuote(
                $this->methodId,
                $quote
            );
            
            // Process the quote
            if ($quote) {
                // Create the order
                $order = $this->quoteManagement->submit($quote);

                // Perform after place order tasks
                if (!$external) {
                    $order = $this->afterPlaceOrder($quote, $order);
                }

                return $order;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('There is no quote available to place an order.')
                );
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
        return $order
        && is_object($order)
        && method_exists($order, 'getId')
        && $order->getId() > 0;
    }

    /**
     * Load an order
     */
    public function getOrder($fields)
    {
        return $this->findOrderByFields($fields);
    }

    /**
     * Gets an order currency
     */
    public function getOrderCurrency($order)
    {
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $storeCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        return ($orderCurrencyCode) ? $orderCurrencyCode : $storeCurrencyCode;
    }

    /**
     * Convert an order amount to integer value for the gateway request.
     */
    public function amountToGateway($amount, $order)
    {
        // Get the order currency
        $currency = $this->getOrderCurrency($order);

        // Get the x1 currency calculation mapping
        $currenciesX1 = explode(
            ',',
            $this->config->getValue('currencies_x1')
        );

        // Get the x1000 currency calculation mapping
        $currenciesX1000 = explode(
            ',',
            $this->config->getValue('currencies_x1000')
        );

        // Prepare the amount
        if (in_array($currency, $currenciesX1)) {
            return $amount;
        } elseif (in_array($currency, $currenciesX1000)) {
            return $amount*1000;
        } else {
            return $amount*100;
        }
    }

    /**
     * Find an order by fields
     */
    public function findOrderByFields($fields)
    {
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
            ->setPageSize(1)
            ->getLastItem();

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

        return $order;
    }
}
