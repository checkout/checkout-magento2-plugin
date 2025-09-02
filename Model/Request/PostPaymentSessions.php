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

namespace CheckoutCom\Magento2\Model\Request;

use Checkout\Payments\Sessions\PaymentSessionsRequest;
use Checkout\Payments\Sessions\PaymentSessionsRequestFactory;
use CheckoutCom\Magento2\Model\Formatter\PriceFormatter;
use CheckoutCom\Magento2\Model\Request\Billing\BillingElement;
use CheckoutCom\Magento2\Model\Request\BillingDescriptor\BillingDescriptorElement;
use CheckoutCom\Magento2\Model\Request\Customer\CustomerElement;
use CheckoutCom\Magento2\Model\Request\Items\ItemsElement;
use CheckoutCom\Magento2\Model\Request\PaymentMethodConfiguration\PaymentMethodConfigurationElement;
use CheckoutCom\Magento2\Model\Request\Risk\RiskElement;
use CheckoutCom\Magento2\Model\Request\Sender\SenderElement;
use CheckoutCom\Magento2\Model\Request\Shipping\ShippingElement;
use CheckoutCom\Magento2\Model\Request\ThreeDS\ThreeDSElement;
use CheckoutCom\Magento2\Model\Resolver\CustomerResolver;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\ExternalSettings;
use CheckoutCom\Magento2\Provider\GeneralSettings;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;

class PostPaymentSessions
{
    protected PaymentSessionsRequestFactory $modelFactory;
    protected BillingDescriptorElement $billingDescriptorElement;
    protected BillingElement $billingElement;
    protected CustomerElement $customerElement;
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
    protected CustomerResolver $customerResolver;

    public function __construct(
        PaymentSessionsRequestFactory $modelFactory,
        BillingDescriptorElement $billingDescriptorElement,
        BillingElement $billingElement,
        CustomerElement $customerElement,
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
        CustomerResolver $customerResolver
    ) {
        $this->modelFactory = $modelFactory;
        
        $this->billingDescriptorElement = $billingDescriptorElement;
        $this->billingElement = $billingElement;
        $this->customerElement = $customerElement;
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
        $this->customerResolver = $customerResolver;
    }

    public function get(CartInterface $quote, array $data): PaymentSessionsRequest {
        $model = $this->modelFactory->create();
        
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $storeCode = $this->storeManager->getStore()->getCode();
        } catch (Exception $error) {
            $websiteCode = null;
            $storeCode = null;
        }
        
        $customer = $this->customerResolver->resolve($quote);

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $currency = $quote->getCurrency()->getBaseCurrencyCode() ?? '';

        $model->amount = $this->priceFormatter->getFormattedPrice($quote->getGrandTotal() ?? 0, $currency);
        $model->currency = $currency;
        $model->billing = $this->billingElement->get($billingAddress);
        $model->success_url = $this->getSuccessUrl($data);
        $model->failure_url = $this->getFailureUrl($data);
        $model->payment_type = "Regular";

        if($this->generalSettings->isDynamicDescriptorEnabled($websiteCode)) {
            $model->billing_descriptor = $this->billingDescriptorElement->get();
        }
        $model->customer = $this->customerElement->get($customer);
        $model->shipping = $this->shippingElement->get($shippingAddress);
        $model->processing_channel_id = $this->accountSettings->getChannelId($websiteCode);
        $model->payment_method_configuration = $this->paymentMethodConfigurationElement->get($customer);
        $model->items = $this->itemsElement->get($quote);
        $model->risk = $this->riskElement->get();
        $model->display_name = $this->externalSettings->getStoreName($storeCode);
        $model->locale = $this->reformatLocale($this->externalSettings->getStoreLocale($storeCode));
        $model->three_ds = $this->threeDSElement->get();
        $model->sender = $this->senderElement->get($customer);
        $model->capture = $this->generalSettings->isAuthorizeAndCapture($websiteCode);
        
        if($this->generalSettings->isAuthorizeAndCapture($websiteCode)) {
            $model->capture_on = $this->getCaptureTime($websiteCode);
        }
        
        return $model;
    }

    protected function getSuccessUrl(array $data): string
    {
        if (isset($data['successUrl'])) {
            return $data['successUrl'];
        }

        try {
            return $this->storeManager->getStore()->getBaseUrl() . 'checkout_com/payment/verify';
        } catch (Exception $error) {
           return '';
        }
    }

    protected function getFailureUrl(array $data): string
    {
        if (isset($data['failureUrl'])) {
            return $data['failureUrl'];
        }
        try {
            return $this->storeManager->getStore()->getBaseUrl() . 'checkout_com/payment/fail';
        } catch (Exception $error) {
           return '';
        }
    }

    protected function getCaptureTime(string $websiteCode): string
    {
        $captureTime = $this->generalSettings->getCaptureTime($websiteCode);
        $captureTime *= 3600;

        $min = $this->generalSettings->getMinCaptureTime($websiteCode);
        
        $timeToAdd = $captureTime >= $min ? $captureTime : $min;

        $captureDate = time() + (int) $timeToAdd;
        
        $dateTime = $this->dateTimeFactory->create();
        $dateTime->setTimestamp($captureDate);

        return $dateTime->format("Y-m-d\TH:i:s\Z");

    }

    protected function reformatLocale(string $locale): string
    {
        return implode("-", explode('_', $locale));
    }
}
