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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class QuoteHandlerService
 */
class QuoteHandlerService
{
    /**
     * $checkoutSession field
     *
     * @var Session $checkoutSession
     */
    private $checkoutSession;
    /**
     * $customerSession field
     *
     * @var Session $customerSession
     */
    private $customerSession;
    /**
     * $cookieManager field
     *
     * @var CookieManagerInterface $cookieManager
     */
    private $cookieManager;
    /**
     * $quoteFactory field
     *
     * @var QuoteFactory $quoteFactory
     */
    private $quoteFactory;
    /**
     * $cartRepository field
     *
     * @var CartRepositoryInterface $cartRepository
     */
    private $cartRepository;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $productRepository field
     *
     * @var ProductRepositoryInterface $productRepository
     */
    private $productRepository;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $shopperHandler field
     *
     * @var ShopperHandlerService $shopperHandler
     */
    private $shopperHandler;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;
    /**
     * $dataObjectFactory field
     *
     * @var DataObjectFactory $dataOjbectFactory
     */
    private $dataObjectFactory;

    /**
     * QuoteHandlerService constructor
     *
     * @param Session                         $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CookieManagerInterface          $cookieManager
     * @param QuoteFactory                    $quoteFactory
     * @param CartRepositoryInterface         $cartRepository
     * @param StoreManagerInterface           $storeManager
     * @param ProductRepositoryInterface      $productRepository
     * @param Config                          $config
     * @param ShopperHandlerService           $shopperHandler
     * @param Logger                          $logger
     * @param DataObjectFactory               $dataObjectFactory
     */
    public function __construct(
        Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        CookieManagerInterface $cookieManager,
        QuoteFactory $quoteFactory,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Config $config,
        ShopperHandlerService $shopperHandler,
        Logger $logger,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->customerSession   = $customerSession;
        $this->cookieManager     = $cookieManager;
        $this->quoteFactory      = $quoteFactory;
        $this->cartRepository    = $cartRepository;
        $this->storeManager      = $storeManager;
        $this->productRepository = $productRepository;
        $this->config            = $config;
        $this->shopperHandler    = $shopperHandler;
        $this->logger            = $logger;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Find a quote
     *
     * @param mixed[] $fields
     *
     * @return CartInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(array $fields = []): CartInterface
    {
        if (!empty($fields)) {
            // Get the quote factory
            $quoteFactory = $this->quoteFactory->create()->getCollection();

            // Add search filters
            foreach ($fields as $key => $value) {
                $quoteFactory->addFieldToFilter(
                    $key,
                    $value
                );
            }

            // Return the first result found
            $quote = $quoteFactory->setPageSize(1)->getLastItem();
        } else {
            // Try to find the quote in session
            $quote = $this->checkoutSession->getQuote();
        }

        return $quote;
    }

    /**
     * Create a new quote
     *
     * @param string|null $currency
     * @param mixed|null  $customer
     *
     * @return CartInterface
     * @throws NoSuchEntityException|LocalizedException
     */
    public function createQuote(string $currency = null, $customer = null): CartInterface
    {
        // Create the quote instance
        $quote = $this->quoteFactory->create();
        $quote->setStore($this->storeManager->getStore());

        // Set the currency
        if ($currency) {
            $quote->setCurrency($currency);
        } else {
            $quote->setCurrency();
        }

        // Set the quote customer
        if ($customer) {
            $quote->assignCustomer($customer);
        } else {
            $quote->assignCustomer($this->shopperHandler->getCustomerData());
        }

        return $quote;
    }

    /**
     * Checks if a quote exists and is valid
     *
     * @param mixed $quote
     *
     * @return bool
     */
    public function isQuote($quote): bool
    {
        return $quote instanceof Quote && $quote->getId() > 0;
    }

    /**
     * Get the order increment id from a quote
     *
     * @param CartInterface $quote
     *
     * @return string|null
     */
    public function getReference(CartInterface $quote): ?string
    {
        $this->cartRepository->save($quote->reserveOrderId());

        return $quote->getReservedOrderId();
    }

    /**
     * Prepares a quote for order placement
     *
     * @param string             $methodId
     * @param CartInterface|null $quote
     *
     * @return Quote|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareQuote(string $methodId, CartInterface $quote = null): ?Quote
    {
        // Find quote and perform tasks
        $quote = $quote ?: $this->getQuote();
        if ($this->isQuote($quote)) {
            // Prepare the inventory
            $quote->setInventoryProcessed(false);

            // Collect totals
            $quote->collectTotals();

            // Check for guest user quote
            if (!$this->customerSession->isLoggedIn() && $quote->getCustomerId() == null) {
                $quote = $this->prepareGuestQuote($quote);
            }

            // Set the payment information
            $payment = $quote->getPayment();
            $payment->setMethod($methodId);

            return $quote;
        }

        return null;
    }

    /**
     * Sets the email for guest users
     *
     * @param CartInterface $quote
     * @param string|null   $email
     *
     * @return CartInterface
     * @throws InputException
     * @throws FailureToSendException
     */
    public function prepareGuestQuote(CartInterface $quote, string $email = null): CartInterface
    {
        // Retrieve the user email
        $guestEmail = ($email) ? $email : $this->findEmail($quote);

        // Set the quote as guest
        $quote->setCustomerId(null)->setCustomerEmail($guestEmail)->setCustomerIsGuest(true)->setCustomerGroupId(
            GroupInterface::NOT_LOGGED_IN_ID
        );

        // Delete the cookie
        $this->cookieManager->deleteCookie(
            $this->config->getValue('email_cookie_name')
        );

        return $quote;
    }

    /**
     * Find a customer email
     *
     * @param CartInterface $quote
     *
     * @return string|null
     */
    public function findEmail(CartInterface $quote): ?string
    {
        // Get an array of possible values
        $emails = [
            $quote->getCustomerEmail(),
            $quote->getBillingAddress()->getEmail(),
            $this->cookieManager->getCookie(
                $this->config->getValue('email_cookie_name')
            ),
        ];

        // Return the first available value
        foreach ($emails as $email) {
            if ($email && !empty($email)) {
                return $email;
            }
        }

        return null;
    }

    /**
     * Gets an array of quote parameters
     *
     * @return mixed[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuoteData(): array
    {
        $data = [
            'value'    => $this->getQuoteValue(),
            'currency' => $this->getQuoteCurrency(),
        ];

        $this->logger->additional($data, 'quote');

        return $data;
    }

    /**
     * Gets a quote currency
     *
     * @param CartInterface|null $quote
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuoteCurrency(CartInterface $quote = null): string
    {
        $quote             = ($quote) ? $quote : $this->getQuote();
        $quoteCurrencyCode = $quote->getQuoteCurrencyCode();
        $storeCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        return ($quoteCurrencyCode) ?: $storeCurrencyCode;
    }

    /**
     * Convert a quote amount to integer value for the gateway request
     *
     * @param float         $amount
     * @param CartInterface $quote
     *
     * @return float|int|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function amountToGateway(float $amount, CartInterface $quote)
    {
        // Get the quote currency
        $currency = $this->getQuoteCurrency($quote);

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
     * Gets a quote value
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuoteValue(): float
    {
        return $this->getQuote()->collectTotals()->getGrandTotal();
    }

    /**
     * Saves the quote. This is needed
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function saveQuote(): void
    {
        $this->cartRepository->save($this->getQuote());
    }

    /**
     * Add product items to a quote
     *
     * @param CartInterface $quote
     * @param mixed[]       $data
     *
     * @return mixed
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function addItems(CartInterface $quote, array $data)
    {
        $items = $this->buildProductData($data);
        foreach ($items as $item) {
            if (isset($item['product_id']) && (int)$item['product_id'] > 0) {
                // Load the product
                $product = $this->productRepository->getById($item['product_id']);

                // Get the quantity
                $quantity = isset($item['qty']) && (int)$item['qty'] > 0 ? $item['qty'] : 1;

                // Add the item
                if (!empty($item['super_attribute'])) {
                    $buyRequest = $this->dataObjectFactory->create()
                        ->setData($items);
                    $quote->addProduct($product, $buyRequest);
                } else {
                    $quote->addProduct($product, $quantity);
                }
            }
        }

        return $quote;
    }

    /**
     * Creates a formatted array with the purchased product data.
     *
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function buildProductData(array $data): array
    {
        // Prepare the base array
        $output = [
            'product_id' => $data['product'],
            'qty'        => $data['qty'],
        ];

        // Add product variations
        if (isset($data['super_attribute']) && !empty($data['super_attribute'])) {
            $output['super_attribute'] = $data['super_attribute'];
        }

        return [$output];
    }

    /**
     * Gets the billing address.
     *
     * @return AddressInterface|Quote\Address|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBillingAddress(): ?AddressInterface
    {
        return $this->getQuote()->getBillingAddress();
    }

    /**
     * Gets quote data for a payment request
     *
     * @param CartInterface $quote
     *
     * @return mixed[]
     */
    public function getQuoteRequestData(CartInterface $quote): array
    {
        $data = [
            'quote_id'       => $quote->getId(),
            'store_id'       => $quote->getStoreId(),
            'customer_email' => $quote->getCustomerEmail(),
        ];

        $this->logger->additional($data, 'quote');

        return $data;
    }

    /**
     *  Prepares the quote filters
     *
     * @param mixed  $paymentDetails
     * @param string $reservedIncrementId
     *
     * @return mixed[]
     */
    public function prepareQuoteFilters($paymentDetails, string $reservedIncrementId): array
    {
        // Prepare the filters array
        $filters = ['increment_id' => $reservedIncrementId];

        // Retrieve the quote metadata
        $quoteData = isset($paymentDetails->metadata['quoteData']) && !empty($paymentDetails->metadata['quoteData']) ? json_decode(
            $paymentDetails->metadata['quoteData'],
            true
        ) : [];

        return array_merge($filters, $quoteData);
    }
}
