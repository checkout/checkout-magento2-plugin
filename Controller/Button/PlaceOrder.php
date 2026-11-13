<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2010-present Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Button;

use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\InstantPurchase\AvailabilityChecker;
use CheckoutCom\Magento2\Model\InstantPurchase\ShippingSelector;
use CheckoutCom\Magento2\Model\Methods\VaultMethod;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\ApiOrderRollback;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PlaceOrder
 */
class PlaceOrder extends Action implements HttpPostActionInterface
{
    private StoreManagerInterface $storeManager;
    private JsonFactory $jsonFactory;
    private QuoteHandlerService $quoteHandler;
    private OrderHandlerService $orderHandler;
    private MethodHandlerService $methodHandler;
    private ApiHandlerService $apiHandler;
    private Utilities $utilities;
    private ShippingSelector $shippingSelector;
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;
    private AddressRepositoryInterface $addressRepository;
    private CustomerSession $customerSession;
    private FormKeyValidator $formKeyValidator;
    private AvailabilityChecker $availabilityChecker;
    private VaultHandlerService $vaultHandler;
    private ApiOrderRollback $apiOrderRollback;

    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        ShippingSelector $shippingSelector,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,
        AddressRepositoryInterface $addressRepository,
        CustomerSession $customerSession,
        FormKeyValidator $formKeyValidator,
        AvailabilityChecker $availabilityChecker,
        VaultHandlerService $vaultHandler,
        ApiOrderRollback $apiOrderRollback
    ) {
        parent::__construct($context);

        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->utilities = $utilities;
        $this->shippingSelector = $shippingSelector;
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
        $this->addressRepository = $addressRepository;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->availabilityChecker = $availabilityChecker;
        $this->vaultHandler = $vaultHandler;
        $this->apiOrderRollback = $apiOrderRollback;
    }

    /**
     * Handles the controller method
     *
     * @return Json
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function execute(): Json
    {
        // Prepare a default error message
        $message = __('An error occurred and the order could not be created.');

        if (!$this->formKeyValidator->validate($this->getRequest())
            || !$this->customerSession->isLoggedIn()
            || !$this->availabilityChecker->isAvailable()
        ) {
            return $this->createResponse($message, false);
        }

        /** @var array $data */
        $data = $this->getRequest()->getParams();
        $data['publicHash'] = (string)($data['instant_purchase_payment_token'] ?? '');

        // The card must belong to the logged-in customer
        if ($this->vaultHandler->getCardFromHash($data['publicHash']) === null) {
            return $this->createResponse($message, false);
        }

        // The addresses must belong to the logged-in customer
        $customerId = (int)$this->customerSession->getCustomerId();
        $billingAddress = $this->getCustomerAddress($data['instant_purchase_billing_address'] ?? null, $customerId);
        $shippingAddress = $this->getCustomerAddress($data['instant_purchase_shipping_address'] ?? null, $customerId);
        if ($billingAddress === null || $shippingAddress === null) {
            return $this->createResponse($message, false);
        }

        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        // Create the quote
        $quote = $this->quoteHandler->createQuote();
        $quote = $this->quoteHandler->addItems(
            $quote,
            $data
        );

        // Set the addresses
        $quote->getBillingAddress()->importCustomerAddressData($billingAddress);
        $quote->getShippingAddress()->importCustomerAddressData($shippingAddress);

        // Set the shipping method
        $shippingMethodCode = $this->shippingSelector->getShippingMethod($quote->getShippingAddress());
        $quote->getShippingAddress()
            ->setShippingMethod($shippingMethodCode)
            ->setCollectShippingRates(true)
            ->collectShippingRates();

        // Set payment
        $quote->setPaymentMethod(VaultMethod::CODE);
        $this->cartRepository->save($quote);
        $quote->getPayment()->importData(['method' => VaultMethod::CODE]);

        // Save the quote
        $this->cartRepository->save($quote->collectTotals());

        // Create the order
        $order = $this->orderHandler->setMethodId(VaultMethod::CODE)->handleOrder($quote);

        // Process the payment
        try {
            $response = $this->methodHandler->get(VaultMethod::CODE)->sendPaymentRequest(
                $data,
                $order->getGrandTotal(),
                $order->getOrderCurrencyCode(),
                $order->getIncrementId(),
                null,
                false,
                null,
                true
            );
        } catch (Exception $e) {
            $this->apiOrderRollback->rollbackOnError($order);

            return $this->createResponse($message, false);
        }

        // Do not keep an unpaid order
        if (!$api->isValidResponse($response)) {
            $this->apiOrderRollback->rollback($order);

            return $this->createResponse($message, false);
        }

        // Add the payment info to the order
        $order = $this->utilities->setPaymentData($order, $response);

        // Save the order
        $this->orderRepository->save($order);

        // Prepare the user response
        $message = __(
            'Your order number %1 has been created successfully.',
            $order->getIncrementId()
        );

        return $this->createResponse($message, true);
    }

    /**
     * Load a customer address only if it belongs to the given customer
     *
     * @param mixed $addressId
     * @param int $customerId
     *
     * @return AddressInterface|null
     */
    private function getCustomerAddress($addressId, int $customerId): ?AddressInterface
    {
        if (!is_numeric($addressId) || $customerId <= 0) {
            return null;
        }

        try {
            $address = $this->addressRepository->getById((int)$addressId);
        } catch (LocalizedException $e) {
            return null;
        }

        if ((int)$address->getCustomerId() !== $customerId) {
            return null;
        }

        return $address;
    }

    /**
     * Creates response with the operation status message.
     *
     * @param Phrase $message
     * @param bool $successMessage
     *
     * @return Json
     */
    public function createResponse(Phrase $message, bool $successMessage): Json
    {
        // Prepare the result
        $result = $this->jsonFactory->create()->setData(['response' => $message]);

        // Prepare the response message
        if ($successMessage) {
            $this->messageManager->addSuccessMessage($message);
        } else {
            $this->messageManager->addErrorMessage($message);
        }

        return $result;
    }
}
