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
        CartRepositoryInterface $cartRepository
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
    }

    public function makePaymentContextRequests(
        string $sourceType,
        ?bool $forceAuthorize = false,
        ?string $paymentType = null,
        ?string $authorizationType = null
    ): array {
        $quote = $this->getQuote();
        if (!$quote->getId() || ($quote->getId() && !$quote->getItemsQty())) {
            return [];
        }

        $request = $this->getContextRequest($quote, $sourceType, $forceAuthorize, $paymentType, $authorizationType);

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
        $splittedName = explode(' ', $paymentRequestsDatas['customer']['name'], 2);
        $lastNameIndex = count($splittedName) === 2 ? 1 : 0;
        $quote->setCustomerFirstname($splittedName[0]);
        $quote->setCustomerLastname($splittedName[1]);
        $quote->setCustomerEmail($paymentRequestsDatas['customer']['email']);

        /** @var AddressInterface $quoteAddress */
        $quoteAddress = $this->addressInterfaceFactory->create();
        $shippingAddressRequesDatas = $paymentRequestsDatas['shipping']['address'];
        $splittedName = explode(' ', $paymentRequestsDatas['shipping']['first_name'], 2);
        $lastNameIndex = count($splittedName) === 2 ? 1 : 0;
        $quoteAddress->setFirstname($splittedName[0]);
        $quoteAddress->setLastname($splittedName[1]);

        $quoteAddress->setCity($shippingAddressRequesDatas['city']);
        $quoteAddress->setCountryId($shippingAddressRequesDatas['country']);
        $quoteAddress->setPostcode($shippingAddressRequesDatas['zip']);

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
        $stateName = $shippingAddressRequesDatas['state'];
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
        string $sourceType,
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

        // Source Type
        $request->source = new AbstractRequestSource($sourceType);

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

            $items[] = $contextItem;
        }

        return $items;
    }

    private function getQuote(): CartInterface
    {
        return $this->checkoutSession->getQuote();
    }
}
