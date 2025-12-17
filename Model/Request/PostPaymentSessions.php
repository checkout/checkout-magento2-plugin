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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Request;

use Checkout\Payments\Sessions\PaymentSessionsRequest;
use Checkout\Payments\Sessions\PaymentSessionsRequestFactory;
use CheckoutCom\Magento2\Model\Formatter\LocaleFormatter;
use CheckoutCom\Magento2\Model\Formatter\PriceFormatter;
use CheckoutCom\Magento2\Model\Request\Billing\BillingElement;
use CheckoutCom\Magento2\Model\Request\BillingDescriptor\BillingDescriptorElement;
use CheckoutCom\Magento2\Model\Request\Customer\CustomerElement;
use CheckoutCom\Magento2\Model\Request\Items\ItemsElement;
use CheckoutCom\Magento2\Model\Request\PaymentMethodAvailability\EnabledDisabledElement;
use CheckoutCom\Magento2\Model\Request\PaymentMethodConfiguration\PaymentMethodConfigurationElement;
use CheckoutCom\Magento2\Model\Request\Risk\RiskElement;
use CheckoutCom\Magento2\Model\Request\Sender\SenderElement;
use CheckoutCom\Magento2\Model\Request\Shipping\ShippingElement;
use CheckoutCom\Magento2\Model\Request\ThreeDS\ThreeDSElement;
use CheckoutCom\Magento2\Model\Resolver\CustomerResolver;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\ExternalSettings;
use CheckoutCom\Magento2\Provider\GeneralSettings;
use Exception;
use Magento\Framework\Url;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class PostPaymentSessions
{
    protected const TAMARA_CURRENCIES = ['AED', 'SAR'];

    protected PaymentSessionsRequestFactory $modelFactory;
    protected BillingDescriptorElement $billingDescriptorElement;
    protected BillingElement $billingElement;
    protected CustomerElement $customerElement;
    protected EnabledDisabledElement $enabledDisabledElement;
    protected ItemsElement $itemsElement;
    protected PaymentMethodConfigurationElement $paymentMethodConfigurationElement;
    protected RiskElement $riskElement;
    protected SenderElement $senderElement;
    protected ShippingElement $shippingElement;
    protected ThreeDSElement $threeDSElement;
    protected AccountSettings $accountSettings;
    protected ExternalSettings $externalSettings;
    protected GeneralSettings $generalSettings;
    private StoreManagerInterface $storeManager;
    private DateTimeFactory $dateTimeFactory;
    protected PriceFormatter $priceFormatter;
    protected LocaleFormatter $localeFormatter;
    protected CustomerResolver $customerResolver;
    protected LoggerInterface $logger;
    protected QuoteHandlerService $quoteHandlerService;
    private ApiHandlerService $apiHandler;
    private SerializerInterface $serializer;
    private QuoteHandlerService $quoteHandler;
    private Url $urlBuilder;

    public function __construct(
        PaymentSessionsRequestFactory $modelFactory,
        BillingDescriptorElement $billingDescriptorElement,
        BillingElement $billingElement,
        CustomerElement $customerElement,
        EnabledDisabledElement $enabledDisabledElement,
        ItemsElement $itemsElement,
        PaymentMethodConfigurationElement $paymentMethodConfigurationElement,
        RiskElement $riskElement,
        SenderElement $senderElement,
        ShippingElement $shippingElement,
        ThreeDSElement $threeDSElement,
        AccountSettings $accountSettings,
        ExternalSettings $externalSettings,
        GeneralSettings $generalSettings,
        StoreManagerInterface $storeManager,
        DateTimeFactory $dateTimeFactory,
        PriceFormatter $priceFormatter,
        LocaleFormatter $localeFormatter,
        CustomerResolver $customerResolver,
        QuoteHandlerService $quoteHandlerService,
        ApiHandlerService $apiHandler,
        SerializerInterface $serializer,
        QuoteHandlerService $quoteHandler,
        Url $urlBuilder,
        LoggerInterface $logger
    ) {
        $this->modelFactory = $modelFactory;
        $this->billingDescriptorElement = $billingDescriptorElement;
        $this->billingElement = $billingElement;
        $this->customerElement = $customerElement;
        $this->enabledDisabledElement = $enabledDisabledElement;
        $this->itemsElement = $itemsElement;
        $this->paymentMethodConfigurationElement = $paymentMethodConfigurationElement;
        $this->senderElement = $senderElement;
        $this->shippingElement = $shippingElement;
        $this->riskElement = $riskElement;
        $this->threeDSElement = $threeDSElement;
        $this->accountSettings = $accountSettings;
        $this->externalSettings = $externalSettings;
        $this->generalSettings = $generalSettings;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->storeManager = $storeManager;
        $this->priceFormatter = $priceFormatter;
        $this->localeFormatter = $localeFormatter;
        $this->customerResolver = $customerResolver;
        $this->logger = $logger;
        $this->quoteHandlerService = $quoteHandlerService;
        $this->apiHandler = $apiHandler;
        $this->serializer = $serializer;
        $this->quoteHandler = $quoteHandler;
        $this->urlBuilder = $urlBuilder;
    }

    public function get(CartInterface $quote, array $data): PaymentSessionsRequest
    {
        $model = $this->modelFactory->create();

        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $storeCode = $this->storeManager->getStore()->getCode();
        } catch (Exception $error) {
            $websiteCode = null;
            $storeCode = null;

            $this->logger->error(
                sprintf('Unable to fetch website code or store code: %s', $error->getMessage()),
            );
        }

        $customer = $this->customerResolver->resolve($quote);
        $data['reference'] = $this->quoteHandlerService->getReference($quote);

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $currency = $quote->getCurrency()->getQuoteCurrencyCode() ?? '';

        $model->amount = $this->priceFormatter->getFormattedPrice($quote->getGrandTotal() ?? 0, $currency);
        $model->currency = $currency;
        $model->billing = $this->billingElement->get($billingAddress);
        $model->success_url = $this->getSuccessUrl($data);
        $model->failure_url = $this->getFailureUrl($data);
        $model->payment_type = 'Regular';

        if ($this->generalSettings->isDynamicDescriptorEnabled($websiteCode)) {
            $model->billing_descriptor = $this->billingDescriptorElement->get();
        }
        $model->customer = $this->customerElement->get($customer, $billingAddress);
        $model->shipping = $this->shippingElement->get($shippingAddress);
        $model->processing_channel_id = $this->accountSettings->getChannelId($websiteCode);
        $model->payment_method_configuration = $this->paymentMethodConfigurationElement->get($customer);
        $model->items = $this->itemsElement->get($quote);
        $model->risk = $this->riskElement->get();
        $model->display_name = $this->externalSettings->getStoreName($storeCode);
        $model->locale = $this->localeFormatter->getFormattedLocale($this->externalSettings->getStoreLocale($storeCode));
        $model->description = __('Payment request')->render();
        $model->three_ds = $this->threeDSElement->get();
        $model->sender = $this->senderElement->get($customer);
        $model->capture = $this->generalSettings->isAuthorizeAndCapture($websiteCode);
        $model->reference = $this->quoteHandlerService->getReference($quote);

        if (in_array($currency, self::TAMARA_CURRENCIES)) {
            $this->customerElement->fillSummary($model->customer, $customer, $currency);
        }

        if ($this->generalSettings->isAuthorizeAndCapture($websiteCode)) {
            $model->capture_on = $this->getCaptureTime($websiteCode);
        }

        if (!isset($model->billing->address->country)) {
            $fallbackCountry = $this->externalSettings->getStoreCountry($storeCode);
            $model->billing->address->country = $fallbackCountry;
        }

        // Add Meta Data
        $customerId = $quote->getCustomerId();
        if ($customerId) {
            $model->metadata['customerId'] = $customerId;
        }
        $model->metadata['quoteData'] = $this->serializer->serialize($this->quoteHandler->getQuoteRequestData($quote));
        $model->metadata = array_merge(
            $model->metadata,
            $this->apiHandler->getBaseMetadata()
        );

        $enabledDisabledElement = $this->enabledDisabledElement->get($model);

        $model->enabled_payment_methods = $enabledDisabledElement['enabled_payment_methods'];
        $model->disabled_payment_methods = $enabledDisabledElement['disabled_payment_methods'];

        return $model;
    }

    protected function getSuccessUrl(array $data): string
    {
        $urlParameters = [];
        if (isset($data['reference'])) {
            $urlParameters['reference'] = $data['reference'];
        }

        if (isset($data['successUrl'])) {
            $param = count($urlParameters) > 0 ? '?' . implode('&', $urlParameters) : '';

            return $data['successUrl'] . $param;
        }

        try {
            return $this->urlBuilder->getUrl(
                'checkout_com/payment/verifyfloworder',
                [
                    '_query' => $urlParameters
                ]
            );
        } catch (Exception $error) {
            $this->logger->error(
                sprintf('Unable to generate success URL: %s', $error->getMessage()),
            );

            return '';
        }
    }

    protected function getFailureUrl(array $data): string
    {
        $urlParameters = [];
        if (isset($data['reference'])) {
            $urlParameters['reference'] = $data['reference'];
        }

        if (isset($data['failureUrl'])) {
            $param = count($urlParameters) > 0 ? '?' . implode('&', $urlParameters) : '';

            return $data['failureUrl'] . $param;
        }

        try {
            return $this->urlBuilder->getUrl(
                'checkout_com/payment/failfloworder',
                [
                    '_query' => $urlParameters
                ]
            );
        } catch (Exception $error) {
            $this->logger->error(
                sprintf('Unable to generate failure URL: %s', $error->getMessage()),
            );

            return '';
        }
    }

    protected function getCaptureTime(string $websiteCode): string
    {
        $captureTime = $this->generalSettings->getCaptureTime($websiteCode);
        $captureTime *= 3600;

        $min = $this->generalSettings->getMinCaptureTime($websiteCode);

        $timeToAdd = $captureTime >= $min ? $captureTime : $min;

        $captureDate = time() + (int)$timeToAdd;

        $dateTime = $this->dateTimeFactory->create();
        $dateTime->setTimestamp($captureDate);

        return $dateTime->format('Y-m-d\TH:i:s\Z');

    }
}
