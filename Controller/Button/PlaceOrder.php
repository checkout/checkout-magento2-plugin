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
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Button;

use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\InstantPurchase\ShippingSelector;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Customer\Model\Address;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PlaceOrder
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class PlaceOrder extends Action
{
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    public $messageManager;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    public $storeManager;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    public $jsonFactory;
    /**
     * $addressManager field
     *
     * @var Address $addressManager
     */
    public $addressManager;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    public $quoteHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    public $orderHandler;
    /**
     * $methodHandler field
     *
     * @var MethodHandlerService $methodHandler
     */
    public $methodHandler;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    public $apiHandler;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    public $utilities;
    /**
     * $shippingSelector field
     *
     * @var ShippingSelector $shippingSelector
     */
    public $shippingSelector;

    /**
     * PlaceOrder constructor
     *
     * @param Context               $context
     * @param ManagerInterface      $messageManager
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory           $jsonFactory
     * @param Address               $addressManager
     * @param QuoteHandlerService   $quoteHandler
     * @param OrderHandlerService   $orderHandler
     * @param MethodHandlerService  $methodHandler
     * @param ApiHandlerService     $apiHandler
     * @param Utilities             $utilities
     * @param ShippingSelector      $shippingSelector
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory,
        Address $addressManager,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        \CheckoutCom\Magento2\Model\InstantPurchase\ShippingSelector $shippingSelector
    ) {
        parent::__construct($context);

        $this->messageManager   = $messageManager;
        $this->storeManager     = $storeManager;
        $this->jsonFactory      = $jsonFactory;
        $this->addressManager   = $addressManager;
        $this->quoteHandler     = $quoteHandler;
        $this->orderHandler     = $orderHandler;
        $this->methodHandler    = $methodHandler;
        $this->apiHandler       = $apiHandler;
        $this->utilities        = $utilities;
        $this->shippingSelector = $shippingSelector;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Set some required properties
        $this->data = $this->getRequest()->getParams();

        // Prepare the public hash
        $this->data['publicHash'] = $this->data['instant_purchase_payment_token'];

        // Set some required properties
        $this->methodId = 'checkoutcom_vault';
    }

    /**
     * Handles the controller method
     *
     * @return Json
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
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
            $this->data
        );

        // Set the billing address
        $billingAddress = $this->addressManager->load($this->data['instant_purchase_billing_address']);
        $quote->getBillingAddress()->addData($billingAddress->getData());

        // Get the shipping address
        $shippingAddress = $this->addressManager->load($this->data['instant_purchase_shipping_address']);

        // Prepare the quote
        $quote->getShippingAddress()->addData($shippingAddress->getData());

        // Set the shipping method
        $shippingMethodCode = $this->shippingSelector->getShippingMethod($quote->getShippingAddress());
        $quote->getShippingAddress()
            ->setShippingMethod($shippingMethodCode)
            ->setCollectShippingRates(true)
            ->collectShippingRates();

        // Set payment
        $quote->setPaymentMethod($this->methodId);
        $quote->save();
        $quote->getPayment()->importData(['method' => $this->methodId]);

        // Save the quote
        $quote->collectTotals()->save();

        // Create the order
        $order = $this->orderHandler->setMethodId($this->methodId)->handleOrder($quote);

        // Process the payment
        $response = $this->methodHandler->get($this->methodId)->sendPaymentRequest(
                $this->data,
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
        $order->save();

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
     * @return Json
     */
    public function createResponse(string $message, bool $successMessage)
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
