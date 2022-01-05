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
use CheckoutCom\Magento2\Model\InstantPurchase\ShippingSelector;
use CheckoutCom\Magento2\Model\Methods\VaultMethod;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PlaceOrder
 */
class PlaceOrder extends Action
{
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    protected $messageManager;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    private $jsonFactory;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * $methodHandler field
     *
     * @var MethodHandlerService $methodHandler
     */
    private $methodHandler;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $shippingSelector field
     *
     * @var ShippingSelector $shippingSelector
     */
    private $shippingSelector;
    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;
    /**
     * $cartRepository field
     *
     * @var CartRepositoryInterface $cartRepository
     */
    private $cartRepository;
    /**
     * $addressRepository field
     *
     * @var AddressRepositoryInterface $addressRepository
     */
    private $addressRepository;
    /**
     * $addressManager field
     *
     * @var Address $addressManager
     */
    private $addressManager;

    /**
     * PlaceOrder constructor
     *
     * @param Context                    $context
     * @param ManagerInterface           $messageManager
     * @param StoreManagerInterface      $storeManager
     * @param JsonFactory                $jsonFactory
     * @param QuoteHandlerService        $quoteHandler
     * @param OrderHandlerService        $orderHandler
     * @param MethodHandlerService       $methodHandler
     * @param ApiHandlerService          $apiHandler
     * @param Utilities                  $utilities
     * @param ShippingSelector           $shippingSelector
     * @param OrderRepositoryInterface   $orderRepository
     * @param CartRepositoryInterface    $cartRepository
     * @param AddressRepositoryInterface $addressRepository
     */
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
        Address $addressManager
    ) {
        parent::__construct($context);

        $this->messageManager    = $messageManager;
        $this->storeManager      = $storeManager;
        $this->jsonFactory       = $jsonFactory;
        $this->quoteHandler      = $quoteHandler;
        $this->orderHandler      = $orderHandler;
        $this->methodHandler     = $methodHandler;
        $this->apiHandler        = $apiHandler;
        $this->utilities         = $utilities;
        $this->shippingSelector  = $shippingSelector;
        $this->orderRepository   = $orderRepository;
        $this->cartRepository    = $cartRepository;
        $this->addressRepository = $addressRepository;
        $this->addressManager    = $addressManager;
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
        /** @var array $data */
        $data               = $this->getRequest()->getParams();
        $data['publicHash'] = $data['instant_purchase_payment_token'];

        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        // Prepare a default error message
        $message = __('An error occurred and the order could not be created.');

        // Create the quote
        $quote = $this->quoteHandler->createQuote();
        $quote = $this->quoteHandler->addItems(
            $quote,
            $data
        );

        // Set the billing address
        /** @var Address $billingAddress */
        $billingAddress = $this->addressManager->load($data['instant_purchase_billing_address']);
        $quote->getBillingAddress()->addData($billingAddress->getData());

        // Get the shipping address
        /** @var Address $shippingAddress */
        $shippingAddress = $this->addressManager->load($data['instant_purchase_shipping_address']);

        // Prepare the quote
        $quote->getShippingAddress()->addData($shippingAddress->getData());

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

        // Add the payment info to the order
        $order = $this->utilities->setPaymentData($order, $response);

        // Save the order
        $this->orderRepository->save($order);

        // Process a successful response
        if ($api->isValidResponse($response)) {
            // Prepare the user response
            $message = __(
                'Your order number %1 has been created successfully.',
                $order->getIncrementId()
            );
        }

        return $this->createResponse($message, true);
    }

    /**
     * Creates response with the operation status message.
     *
     * @param Phrase $message
     * @param bool   $successMessage
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
