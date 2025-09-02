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

use Checkout\Common\Product as CheckoutProduct;
use Checkout\Payments\BillingDescriptor;
use Checkout\Payments\PaymentType;
use Checkout\Payments\Sessions\PaymentSessionsRequestFactory;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Methods\PayByLinkMethod;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentLinkRequest;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentLinkRequestFactory;
use CheckoutCom\Magento2\Model\Request\Billing\BillingElement;
use CheckoutCom\Magento2\Model\Request\Risk\RiskElement;
use CheckoutCom\Magento2\Model\Request\Shipping\ShippingElement;
use CheckoutCom\Magento2\Model\Request\ThreeDS\ThreeDSElement;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\ExternalSettings;
use CheckoutCom\Magento2\Provider\FlowMethodSettings;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PostPaymentLinks
{
    private Session $backendAuthSession;
    private ManagerInterface $messageManager;
    private ApiHandlerService $apiHandler;
    private OrderHandlerService $orderHandler;
    private Config $config;
    private Utilities $utilities;
    private Logger $logger;
    private BillingElement $billingElement;
    private StoreManagerInterface $storeManager;
    private ExternalSettings $externalSettings;
    private AccountSettings $accountSettings;
    private ShippingElement $shippingElement;
    private OrderHandlerService $orderHandlerService;
    private ThreeDSElement $threeDSElement;
    private RiskElement $riskElement;
    private FlowMethodSettings $flowMethodSettings;
    private PaymentLinkRequestFactory $paymentLinkRequestFactory;

    public function __construct(
        Session $backendAuthSession,
        ManagerInterface $messageManager,
        ApiHandlerService $apiHandler,
        OrderHandlerService $orderHandler,
        Config $config,
        Utilities $utilities,
        Logger $logger,
        BillingElement $billingElement,
        ExternalSettings $externalSettings,
        AccountSettings $accountSettings,
        StoreManagerInterface $storeManager,
        ShippingElement $shippingElement,
        OrderHandlerService $orderHandlerService,
        ThreeDSElement $threeDSElement,
        RiskElement $riskElement,
        FlowMethodSettings $flowMethodSettings,
        PaymentLinkRequestFactory $paymentLinkRequestFactory
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->messageManager = $messageManager;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->billingElement = $billingElement;
        $this->externalSettings = $externalSettings;
        $this->accountSettings = $accountSettings;
        $this->storeManager = $storeManager;
        $this->shippingElement = $shippingElement;
        $this->orderHandlerService = $orderHandlerService;
        $this->threeDSElement = $threeDSElement;
        $this->riskElement = $riskElement;
        $this->flowMethodSettings = $flowMethodSettings;
        $this->paymentLinkRequestFactory = $paymentLinkRequestFactory;
    }

    public function get(OrderInterface $order, ApiHandlerService $api): PaymentLinkRequest{
        $methodId = $order->getPayment()->getMethodInstance()->getCode();
        $storeCode = $order->getStore()->getCode();
        $websiteCode = $this->storeManager->getStore($storeCode)->getWebsite()->getCode();
        $shippingAddress = $order->getShippingAddress();
        $products = [];
        /** @var PaymentLinkRequest $request */
        $request = $this->paymentLinkRequestFactory->create();
        $request->amount = $this->preparePayByLinkAmount($order);
        $request->currency = $order->getOrderCurrencyCode();
        $request->billing = $this->billingElement->get($order->getBillingAddress());
        $request->payment_type = PaymentType::$regular;
        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $billingDescriptor = new BillingDescriptor();
            $billingDescriptor->name = $this->config->getValue('descriptor_name');
            $billingDescriptor->city = $this->config->getValue('descriptor_city');

            $request->billing_descriptor = $billingDescriptor;
        }
        $request->reference = $order->getIncrementId();
        $request->processing_channel_id = $this->accountSettings->getChannelId($websiteCode);
        $request->expires_in = (int)$this->config->getValue('cancel_order_link_after', PayByLinkMethod::CODE, $storeCode, ScopeInterface::SCOPE_STORE);
        $request->customer = $api->createCustomer($order);
        if ($shippingAddress) {
            $request->shipping = $this->shippingElement->get($shippingAddress);
        }
        $request->allow_payment_methods = $this->flowMethodSettings->getAllowedPaymentMethods($storeCode);
        $request->disabled_payment_methods = $this->flowMethodSettings->getDisabledPaymentMethods($storeCode);

        foreach ($order->getAllVisibleItems() as $item) {
            $unitPrice = $this->orderHandlerService->amountToGateway(
                $this->utilities->formatDecimals($item->getPriceInclTax()),
                $order
            );
            $product = new CheckoutProduct();
            $product->name = $item->getName();
            $product->quantity = (int)$item->getQtyOrdered();
            $product->price = $unitPrice;
            $products[] = $product;
        }
        $request->products = $products;
        $request->three_ds = $this->threeDSElement->get();
        $request->risk = $this->riskElement->get();
        $request->locale = implode("-", explode('_', $this->externalSettings->getStoreLocale($storeCode)));

        // Prepare the metadata array
        $request->metadata = array_merge(
            ['methodId' => $methodId],
            $this->apiHandler->getBaseMetadata()
        );
        return $request;
    }


    /**
     * Prepare the payment amount for the MOTO payment request
     *
     * @param Order $order
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function preparePayByLinkAmount(Order $order): float
    {
        // Get the payment instance
        $amount = $order->getGrandTotal();

        // Return the formatted amount
        return $this->orderHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $order
        );
    }
}
