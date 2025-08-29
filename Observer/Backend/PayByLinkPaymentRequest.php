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

namespace CheckoutCom\Magento2\Observer\Backend;

use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use Checkout\Common\Product as CheckoutProduct;
use Checkout\Payments\BillingDescriptor;
use Checkout\Payments\PaymentType;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Methods\PayByLinkMethod;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentLinkRequest;
use CheckoutCom\Magento2\Model\Request\Billing\BillingElement;
use CheckoutCom\Magento2\Model\Request\Customer\CustomerElement;
use CheckoutCom\Magento2\Model\Request\Risk\RiskElement;
use CheckoutCom\Magento2\Model\Request\Sender\SenderElement;
use CheckoutCom\Magento2\Model\Request\Shipping\ShippingElement;
use CheckoutCom\Magento2\Model\Request\ThreeDS\ThreeDSElement;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\ExternalSettings;
use CheckoutCom\Magento2\Provider\FlowMethodSettings;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\Url as BackendUrl;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PayByLinkPaymentRequest
 */
class PayByLinkPaymentRequest implements ObserverInterface
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
        CustomerElement $customerElement,
        ShippingElement $shippingElement,
        OrderHandlerService $orderHandlerService,
        ThreeDSElement $threeDSElement,
        RiskElement $riskElement,
        SenderElement $senderElement,
        FlowMethodSettings $flowMethodSettings,
        BackendUrl $backendUrl
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
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     * @throws CheckoutArgumentException
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CheckoutApiException
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $methodId = $order->getPayment()->getMethodInstance()->getCode();
        $storeCode = $order->getStore()->getCode();
        $websiteCode = $this->storeManager->getStore($storeCode)->getWebsite()->getCode();
        $shippingAddress = $order->getShippingAddress();
        $products = [];

        // Process the payment
        if ($this->needsPayByLinkProcessing($methodId, $order)) {
            // Prepare the response container
            $response = null;

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            // Set the payment
            $request = new PaymentLinkRequest();
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

            // Send the charge request
            try {
                $this->logger->display($request);
                $response = $api->getCheckoutApi()->getPaymentLinksClient()->createPaymentLink($request);
                $this->logger->display($response);
            } catch (CheckoutApiException $e) {
                $this->logger->write($e->getMessage());
            } finally {
                // Add the response link to the order payment data
                if (is_array($response) && $api->isValidResponse($response)) {
                    $order
                        ->setStatus($this->config->getValue('order_status_waiting_payment', PayByLinkMethod::CODE, $storeCode, ScopeInterface::SCOPE_STORE))
                        ->getPayment()->setAdditionalInformation(PayByLinkMethod::ADDITIONAL_INFORMATION_LINK_CODE, $response['_links']['redirect']['href']);
                    if (isset($response['status'])) {
                        if ($response['status'] === 'Authorized') {
                            $this->messageManager->addSuccessMessage(
                                __('The payment link request was successfully processed.')
                            );
                        } else {
                            $this->messageManager->addWarningMessage(__('Status: %1', $response['status']));
                        }
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __('The payment link request could not be processed. Please check the payment details.')
                    );
                }
            }
        }
    }

    /**
     * @param string $methodId
     * @param array $params
     *
     * @return bool
     */
    protected function needsPayByLinkProcessing(string $methodId, OrderInterface $order): bool
    {
        return $this->backendAuthSession->isLoggedIn() && $methodId === PayByLinkMethod::CODE && !$order->getPayment()->getAdditionalInformation(PayByLinkMethod::ADDITIONAL_INFORMATION_LINK_CODE);
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
    protected function preparePayByLinkAmount(Order $order): float
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
