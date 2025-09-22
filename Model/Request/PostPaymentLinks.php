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

use Checkout\Payments\PaymentType;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Formatter\LocaleFormatter;
use CheckoutCom\Magento2\Model\Formatter\PriceFormatter;
use CheckoutCom\Magento2\Model\Methods\PayByLinkMethod;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentLinkRequest;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentLinkRequestFactory;
use CheckoutCom\Magento2\Model\Request\Billing\BillingElement;
use CheckoutCom\Magento2\Model\Request\BillingDescriptor\BillingDescriptorElement;
use CheckoutCom\Magento2\Model\Request\PaymentMethodAvailability\EnabledDisabledElement;
use CheckoutCom\Magento2\Model\Request\Product\ProductElement;
use CheckoutCom\Magento2\Model\Request\Risk\RiskElement;
use CheckoutCom\Magento2\Model\Request\Shipping\ShippingElement;
use CheckoutCom\Magento2\Model\Request\ThreeDS\ThreeDSElement;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\ExternalSettings;
use CheckoutCom\Magento2\Provider\GeneralSettings;
use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
class PostPaymentLinks
{
    private ApiHandlerService $apiHandler;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private AccountSettings $accountSettings;
    private ExternalSettings $externalSettings;
    private GeneralSettings $generalSettings;
    private PaymentLinkRequestFactory $paymentLinkRequestFactory;
    private BillingElement $billingElement;
    private BillingDescriptorElement $billingDescriptorElement;
    private EnabledDisabledElement $enabledDisabledElement;
    private ProductElement $productElement;
    private RiskElement $riskElement;
    private ShippingElement $shippingElement;
    private ThreeDSElement $threeDSElement;
    private LocaleFormatter $localeFormatter;
    private PriceFormatter $priceFormatter;
    private LoggerInterface $logger;

    public function __construct(
        ApiHandlerService $apiHandler,
        Config $config,
        StoreManagerInterface $storeManager,
        PaymentLinkRequestFactory $paymentLinkRequestFactory,
        BillingElement $billingElement,
        BillingDescriptorElement $billingDescriptorElement,
        EnabledDisabledElement $enabledDisabledElement,
        ProductElement $productElement,
        RiskElement $riskElement,
        ShippingElement $shippingElement,
        ThreeDSElement $threeDSElement,
        LocaleFormatter $localeFormatter,
        PriceFormatter $priceFormatter,
        AccountSettings $accountSettings,
        ExternalSettings $externalSettings,
        GeneralSettings $generalSettings,
        LoggerInterface $logger
    ) {
        $this->apiHandler = $apiHandler;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->paymentLinkRequestFactory = $paymentLinkRequestFactory;
        $this->billingElement = $billingElement;
        $this->billingDescriptorElement = $billingDescriptorElement;
        $this->enabledDisabledElement = $enabledDisabledElement;
        $this->productElement = $productElement;
        $this->riskElement = $riskElement;
        $this->shippingElement = $shippingElement;
        $this->threeDSElement = $threeDSElement;
        $this->localeFormatter = $localeFormatter;
        $this->priceFormatter = $priceFormatter;
        $this->accountSettings = $accountSettings;
        $this->externalSettings = $externalSettings;
        $this->generalSettings = $generalSettings;
        $this->logger = $logger;
    }

    public function get(OrderInterface $order, ApiHandlerService $api): PaymentLinkRequest
    {
        try {
            $storeCode = $order->getStore()->getCode();
            $websiteCode = $this->storeManager->getStore($storeCode)->getWebsite()->getCode();
        } catch (Exception $error) {
            $websiteCode = null;
            $storeCode = null;

            $this->logger->error(
                sprintf("Unable to fetch website code or store code: %s", $error->getMessage()),
            );
        }
        $methodId = $order->getPayment()->getMethodInstance()->getCode();
        $shippingAddress = $order->getShippingAddress();
        $products = [];
        $currency = $order->getOrderCurrencyCode() ?? '';
        /** @var PaymentLinkRequest $request */
        $request = $this->paymentLinkRequestFactory->create();

        $request->amount = $this->priceFormatter->getFormattedPrice($order->getGrandTotal() ?? 0, $currency);
        $request->currency = $currency;
        $request->billing = $this->billingElement->get($order->getBillingAddress());
        $request->payment_type = PaymentType::$regular;
        if ($this->generalSettings->isDynamicDescriptorEnabled($websiteCode)) {
            $request->billing_descriptor = $this->billingDescriptorElement->get();
        }
        $request->customer = $api->createCustomer($order);
        if ($shippingAddress) {
            $request->shipping = $this->shippingElement->get($shippingAddress);
        }
        $request->processing_channel_id = $this->accountSettings->getChannelId($websiteCode);
        $request->products = $this->productElement->get($order);
        $request->risk = $this->riskElement->get();
        $request->locale = $this->localeFormatter->getFormattedLocale($this->externalSettings->getStoreLocale($storeCode));
        $request->three_ds = $this->threeDSElement->get();
        $request->reference = $order->getIncrementId();
        $request->expires_in = (int)$this->config->getValue('cancel_order_link_after', PayByLinkMethod::CODE, $storeCode, ScopeInterface::SCOPE_STORE);
        $request->products = $products;
        
        // Prepare the metadata array
        $request->metadata = array_merge(
            ['methodId' => $methodId],
            $this->apiHandler->getBaseMetadata()
        );

        $enabledDisabledElement = $this->enabledDisabledElement->get($request);
        $request->allow_payment_methods = $enabledDisabledElement['enabled_payment_methods'];
        $request->disabled_payment_methods = $enabledDisabledElement['disabled_payment_methods'];

        return $request;
    }
}
