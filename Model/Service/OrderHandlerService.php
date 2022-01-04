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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class OrderHandlerService
 */
class OrderHandlerService
{
    /**
     * $checkoutSession field
     *
     * @var Session $checkoutSession
     */
    private $checkoutSession;
    /**
     * $quoteManagement field
     *
     * @var QuoteManagement $quoteManagement
     */
    private $quoteManagement;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;
    /**
     * $searchBuilder field
     *
     * @var SearchCriteriaBuilder $searchBuilder
     */
    private $searchBuilder;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $methodId field
     *
     * @var  $methodId
     */
    protected $methodId;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;
    /**
     * $transactionHandler field
     *
     * @var TransactionHandlerService $transactionHandler
     */
    private $transactionHandler;

    /**
     * OrderHandlerService constructor
     *
     * @param Session                   $checkoutSession
     * @param QuoteManagement           $quoteManagement
     * @param OrderRepositoryInterface  $orderRepository
     * @param SearchCriteriaBuilder     $searchBuilder
     * @param Config                    $config
     * @param QuoteHandlerService       $quoteHandler
     * @param StoreManagerInterface     $storeManager
     * @param Logger                    $logger
     * @param TransactionHandlerService $transactionHandler
     */
    public function __construct(
        Session $checkoutSession,
        QuoteManagement $quoteManagement,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchBuilder,
        Config $config,
        QuoteHandlerService $quoteHandler,
        StoreManagerInterface $storeManager,
        Logger $logger,
        TransactionHandlerService $transactionHandler
    ) {
        $this->checkoutSession    = $checkoutSession;
        $this->quoteManagement    = $quoteManagement;
        $this->orderRepository    = $orderRepository;
        $this->searchBuilder      = $searchBuilder;
        $this->config             = $config;
        $this->quoteHandler       = $quoteHandler;
        $this->storeManager       = $storeManager;
        $this->logger             = $logger;
        $this->transactionHandler = $transactionHandler;
    }

    /**
     * Set the payment method id
     *
     * @param string $methodId
     *
     * @return $this
     */
    public function setMethodId(string $methodId): OrderHandlerService
    {
        $this->methodId = $methodId;

        return $this;
    }

    /**
     * Places an order if not already created
     *
     * @param null  $quote
     * @param false $external
     *
     * @return AbstractExtensibleModel|OrderInterface|mixed|object|null
     * @throws LocalizedException
     */
    public function handleOrder($quote = null, bool $external = false)
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
                throw new LocalizedException(
                    __('There is no quote available to place an order.')
                );
            }
        } else {
            throw new LocalizedException(
                __('A payment method ID is required to place an order.')
            );
        }
    }

    /**
     * Checks if an order exists and is valid
     *
     * @param mixed $order
     *
     * @return bool
     */
    public function isOrder($order): bool
    {
        return $order instanceof Order && $order->getId() > 0;
    }

    /**
     * Load an order
     *
     * @param $fields
     *
     * @return OrderInterface
     */
    public function getOrder($fields): OrderInterface
    {
        return $this->findOrderByFields($fields);
    }

    /**
     * Gets an order currency
     *
     * @param $order
     *
     * @return mixed|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getOrderCurrency($order)
    {
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $storeCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        return ($orderCurrencyCode) ?: $storeCurrencyCode;
    }

    /**
     * Convert an order amount to integer value for the gateway request
     *
     * @param $amount
     * @param $order
     *
     * @return float|int|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
            return $amount * 1000;
        } else {
            return $amount * 100;
        }
    }

    /**
     * Find an order by fields
     *
     * @param $fields
     *
     * @return OrderInterface
     */
    public function findOrderByFields($fields): OrderInterface
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
        /** @var OrderInterface $order */
        $order = $this->orderRepository->getList($search)->setPageSize(1)->getLastItem();

        if ($order->getId()) {
            $this->logger->additional($this->getOrderDetails($order), 'order');
        }

        return $order;
    }

    /**
     * Tasks after place order
     *
     * @param $quote
     * @param $order
     *
     * @return mixed
     */
    public function afterPlaceOrder($quote, $order)
    {
        // Prepare session quote info for redirection after payment
        $this->checkoutSession->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();

        // Prepare session order info for redirection after payment
        $this->checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        return $order;
    }

    /**
     * Get status history by id
     *
     * @param $entity
     * @param $order
     *
     * @return false|mixed
     */
    public function getStatusHistoryByEntity($entity, $order)
    {
        foreach ($order->getStatusHistoryCollection() as $status) {
            if ($status->getEntityName() == $entity) {
                return $status;
            }
        }

        return false;
    }

    /**
     * Return common order details for additional logging.
     *
     * @param $order
     *
     * @return array
     */
    public function getOrderDetails($order)
    {
        return [
            'id'           => $order->getId(),
            'increment_id' => $order->getIncrementId(),
            'state'        => $order->getState(),
            'status'       => $order->getStatus(),
            'grand_total'  => $order->getGrandTotal(),
            'currency'     => $order->getOrderCurrencyCode(),
            'payment'      => [
                'method_id' => $order->getPayment() ? $order->getPayment()->getMethodInstance()->getCode() : null,
            ],
            'transactions' => $this->transactionHandler->getTransactionDetails($order),
        ];
    }
}
