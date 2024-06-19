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

use Checkout\Payments\AuthorizationType;
use Checkout\Payments\Contexts\PaymentContextsItems;
use Checkout\Payments\Contexts\PaymentContextsRequest;
use Checkout\Payments\PaymentType;
use Checkout\Payments\ProcessingSettings;
use Checkout\Payments\Request\Source\AbstractRequestSource;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as MagentoLoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PaymentContextRequestService
{
    protected StoreManagerInterface $storeManager;
    protected ApiHandlerService $apiHandlerService;
    protected Session $checkoutSession;
    protected Config $checkoutConfigProvider;
    protected UrlInterface $urlBuilder;
    protected MagentoLoggerHelper $ckoLogger;
    protected Utilities $utilities;
    protected AddressInterfaceFactory $addressInterfaceFactory;
    protected CartRepositoryInterface $cartRepository;
    protected RegionCollectionFactory $regionCollectionFactory;
    protected ShopperHandlerService $shopperHandlerService;

    public function __construct(
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        Session $checkoutSession,
        Config $checkoutConfigProvider,
        UrlInterface $urlBuilder,
        ApiHandlerService $apiHandlerService,
        MagentoLoggerHelper $ckoLogger,
        Utilities $utilities,
        AddressInterfaceFactory $addressInterfaceFactory,
        RegionCollectionFactory $regionCollectionFactory,
        CartRepositoryInterface $cartRepository,
        ShopperHandlerService $shopperHandlerService
    ) {
        $this->storeManager = $storeManager;
        $this->apiHandlerService = $apiHandler;
        $this->checkoutConfigProvider = $checkoutConfigProvider;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->ckoLogger = $ckoLogger;
        $this->utilities = $utilities;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->cartRepository = $cartRepository;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->shopperHandlerService = $shopperHandlerService;
    }

    public function makePaymentContextRequests(
        AbstractRequestSource $source,
        ?bool $forceAuthorize = false,
        ?string $paymentType = null,
        ?string $authorizationType = null
    ): array {
        $quote = $this->getQuote();
        if (!$quote->getId() || ($quote->getId() && !$quote->getItemsQty())) {
            return [];
        }

        $request = $this->getContextRequest($quote, $source, $forceAuthorize, $paymentType, $authorizationType);

        $this->ckoLogger->additional($this->utilities->objectToArray($request), 'payment');

        $storeCode = $this->storeManager->getStore($quote->getStoreId())->getCode();
        $api = $this->apiHandlerService->init($storeCode, ScopeInterface::SCOPE_STORE);

        return $api->getCheckoutApi()
            ->getPaymentContextsClient()
            ->createPaymentContexts($request);
    }

    public function getPaymentContextById(string $paymentContextId, int $storeId, ?bool $refreshQuote = false, ?PaymentInterface $paymentMethod = null): array
    {
        $storeCode = $this->storeManager->getStore($storeId)->getCode();
        $api = $this->apiHandlerService->init($storeCode, ScopeInterface::SCOPE_STORE);
        try {
            $contextDatas = $api->getCheckoutApi()->getPaymentContextsClient()->getPaymentContextDetails($paymentContextId);
            if ($refreshQuote) {
                $this->refreshQuoteWithPaymentContext($contextDatas, $paymentMethod);
            }

            return $contextDatas;
        } catch (Exception $e) {
            return [];
        }
    }

    public function refreshQuoteWithPaymentContext(array $contextDatas, ?PaymentInterface $paymentMethod = null): void
    {
        if (empty($contextDatas)) {
            return;
        }

        $quote = $this->getQuote();
        $paymentRequestsDatas = $contextDatas['payment_request'];
        $name = $paymentRequestsDatas['customer']['name'] ? explode(' ', $paymentRequestsDatas['customer']['name'], 2) : [];
        $quote->setCustomerFirstname($name[0] ?? $quote->getCustomerFirstname());
        $quote->setCustomerLastname($name[1] ?? $quote->getCustomerLastname());
        $quote->setCustomerEmail($paymentRequestsDatas['customer']['email'] ?? $quote->getCustomerEmail());

        /** @var AddressInterface $quoteAddress */
        $quoteAddress = $this->addressInterfaceFactory->create();
        $shippingAddressRequesDatas = $paymentRequestsDatas['shipping']['address'];
        $shippingName = $paymentRequestsDatas['shipping']['first_name'] ? explode(' ', $paymentRequestsDatas['shipping']['first_name'], 2) : [];
        $quoteAddress->setFirstname($shippingName[0] ?? $quoteAddress->getFirstname());
        $quoteAddress->setLastname($shippingName[1] ?? $quoteAddress->getLastname());
        $quoteAddress->setCity($shippingAddressRequesDatas['city'] ?? $quoteAddress->getCity());
        $quoteAddress->setCountryId($shippingAddressRequesDatas['country'] ?? $quoteAddress->getCountry());
        $quoteAddress->setPostcode($shippingAddressRequesDatas['zip'] ?? $quoteAddress->getPostcode());

        $streets = [];
        $i = 1;
        while ($i < 4) {
            if (!empty($shippingAddressRequesDatas['address_line' . $i])) {
                $streets[] = $shippingAddressRequesDatas['address_line' . $i];
            }
            $i++;
        }
        $quoteAddress->setStreet($streets);

        // Manage region
        $stateName = $shippingAddressRequesDatas['state'] ?? null;
        if ($stateName) {
            $regionCollection = $this->regionCollectionFactory->create();
            $region = $regionCollection->addFieldToFilter('default_name', ['eq' => $stateName])->getFirstItem();
            if ($region->getId()) {
                $quoteAddress->setRegionCode($region->getCode());
                $quoteAddress->setRegionId($region->getRegionId());
            }
        }

        // Set Payment method if given
        if ($paymentMethod) {
            $quote->setPayment($paymentMethod);
        }

        //Assign addresse and save quote
        $quote->setBillingAddress($quoteAddress)->setShippingAddress($quoteAddress);
        $this->cartRepository->save($quote);
    }

    private function getContextRequest(
        CartInterface $quote,
        AbstractRequestSource $source,
        ?bool $forceAuthorize = false,
        ?string $paymentType = null,
        ?string $authorizationType = null
    ): PaymentContextsRequest {
        // Set Default values
        if (!$paymentType) {
            $paymentType = PaymentType::$regular;
        }
        if (!$authorizationType) {
            $authorizationType = AuthorizationType::$final;
        }
        $capture = $forceAuthorize ? false : $this->checkoutConfigProvider->needsAutoCapture();

        // Global informations
        $request = new PaymentContextsRequest();
        $request->amount = $this->utilities->formatDecimals($quote->getGrandTotal() * 100);
        $request->payment_type = $paymentType;
        $request->currency = $quote->getCurrency()->getQuoteCurrencyCode();
        $request->capture = $capture;
        $request->processing_channel_id = $this->checkoutConfigProvider->getValue('channel_id');

        $shipping = $quote->getShippingAddress();
        if ($shipping->getShippingDescription() && $shipping->getShippingInclTax() > 0) {
            $processing = new ProcessingSettings();
            $processing->shipping_amount = $this->utilities->formatDecimals($shipping->getShippingInclTax() * 100);
            $processing->locale = str_replace('_', '-', $this->shopperHandlerService->getCustomerLocale());
            $request->processing = $processing;
        }

        // Source
        $request->source = $source;

        // Items
        $request->items = $this->getRequestItems($quote);

        // Urls
        $request->success_url = $this->urlBuilder->getUrl('checkout/onepage/success');
        $request->failure_url = $this->urlBuilder->getUrl('checkout/onepage/failure');

        return $request;
    }

    public function getRequestItems(CartInterface $quote): array
    {
        $items = [];
        /** @var Quote\Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $discount = $this->utilities->formatDecimals($item->getDiscountAmount()) * 100;
            $rowAmount = ($this->utilities->formatDecimals($item->getRowTotalInclTax()) * 100) -
                         ($this->utilities->formatDecimals($discount));
            $unitPrice = ($this->utilities->formatDecimals($item->getRowTotalInclTax() / $item->getQty()) * 100) -
                         ($this->utilities->formatDecimals($discount / $item->getQty()));
            // Api does not accept 0 prices
            if (!$unitPrice) {
                continue;
            }

            $contextItem = new PaymentContextsItems();
            $contextItem->reference = $item->getSku();
            $contextItem->quantity = $item->getQty();
            $contextItem->name = $item->getName();
            $contextItem->discount_amount = $discount;
            $contextItem->unit_price = $unitPrice;
            $contextItem->total_amount = $rowAmount;

            $items[] = $contextItem;
        }

        return $items;
    }

    private function getQuote(): CartInterface
    {
        return $this->checkoutSession->getQuote();
    }
}
