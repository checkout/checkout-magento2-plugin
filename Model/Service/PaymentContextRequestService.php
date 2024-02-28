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
use Checkout\Payments\Request\Source\AbstractRequestSource;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as MagentoLoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
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

    public function __construct(
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        Session $checkoutSession,
        Config $checkoutConfigProvider,
        UrlInterface $urlBuilder,
        ApiHandlerService $apiHandlerService,
        MagentoLoggerHelper $ckoLogger,
        Utilities $utilities
    ) {
        $this->storeManager = $storeManager;
        $this->apiHandlerService = $apiHandler;
        $this->checkoutConfigProvider = $checkoutConfigProvider;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->ckoLogger = $ckoLogger;
        $this->utilities = $utilities;
    }

    public function makePaymentContextRequests(
        string $sourceType,
        ?bool $forceAuthorize = false,
        ?string $paymentType = null,
        ?string $authorizationType = null
    ): array {
        $quote = $this->getQuote();
        if (!$quote->getId()) {
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

    private function getContextRequest(
        Quote | CartInterface $quote,
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
        $items = [];
        /** @var Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            $contextItem = new PaymentContextsItems();
            $contextItem->reference = $item->getSku();
            $contextItem->quantity = $item->getQty();
            $contextItem->name = $item->getName();
            $contextItem->unit_price = $this->utilities->formatDecimals($item->getRowTotalInclTax() / $item->getQty()) * 100;

            $items[] = $contextItem;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
            $product = new PaymentContextsItems();
            $product->name = $shipping->getShippingDescription();
            $product->quantity = 1;
            $product->unit_price = $shipping->getShippingInclTax() * 100;

            $items[] = $product;
        }
        $request->items = $items;

        // Urls
        $request->success_url = $this->urlBuilder->getUrl('checkout/onepage/success');
        $request->failure_url = $this->urlBuilder->getUrl('checkout/onepage/failure');

        return $request;
    }

    private function getQuote(): Quote | CartInterface
    {
        return $this->checkoutSession->getQuote();
    }

}
