<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Block\Adminhtml\Order\View;

use Checkout\CheckoutApi;
use CheckoutCom\Magento2\Helper\Logger;
use Checkoutcom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Api\V3;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Framework\App\Request\Http;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class View extends \Magento\Backend\Block\Template
{
    /**
     * $checkoutApi field
     *
     * @var CheckoutApi $checkoutApi
     */
    protected $checkoutApi;

    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;

    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;

    /**
     * $api field
     *
     * @var V3 $api
     */
    private $api;

    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;

    /**
     * $request field
     *
     * @var Http $request
     */
    private $request;

    /**
     * $objectManager field
     *
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Utilities $utilities
     * @param ObjectManagerInterface $objectManager
     * @param V3 $api
     * @param ApiHandlerService $apiHandler
     * @param CheckoutApi $checkoutApi
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Utilities $utilities,
        ObjectManagerInterface $objectManager,
        V3 $api,
        ApiHandlerService $apiHandler,
        CheckoutApi $checkoutApi,
        StoreManagerInterface $storeManager,
        Http $request,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->utilities = $utilities;
        $this->_objectManager = $objectManager;
        $this->api = $api;
        $this->apiHandler = $apiHandler;
        $this->checkoutApi = $checkoutApi;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Get order payment id
     *
     * @return string
     */
    public function getPaymentId()
    {
        $paymentId = $this->getOrder()->getPayment()->getId();

        return $paymentId;
    }

    /**
     * Get information about card used for payment
     *
     * @param string $paymentId
     *
     * @return string
     */
    public function getCardInformation($paymentId)
    {
        // Get store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $rep = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        $validResponse = $this->apiHandler->isValidResponse($test);

        return $response;
    }

    /**
     * Get Section Name in BO
     *
     * @return string
     */
    public function sectionName()
    {
        return "Payment Additional Information";
    }

    /**
     * Get order
     *
     * @return OrderInterface
     */
    public function getOrder(): OrderInterface
    {
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($this->getRequest()->getParam('order_id'));

        return $order;
    }

    /**
     * Get payment card data
     *
     * @param OrderInterface $order
     *
     * @return array
     */
    public function getPaymentData(OrderInterface $order): ?array
    {
        return $this->utilities->getPaymentData($order);
    }

    /**
     * Get card 3DS informations
     *
     * @param OrderInterface $order
     *
     * @return array
     */
    public function getThreeDs(OrderInterface $order): ?array
    {
        return $this->utilities->getThreeDs($order);
    }

    /**
     * Get card type
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getCardType(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['card_type'] ?? null) {
            return 'Card type : ' . $paymentData['card_type'];
        } else {
            return null;
        }
    }

    /**
     * Get card last four digits
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getFourDigits(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['last4'] ?? null) {
            return 'Card 4 last numbers : ' . $paymentData['last4'];
        } else {
            return null;
        }
    }

    /**
     * Get card expiry month
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getCardExpiryMonth(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['expiry_month'] ?? null) {
            return 'Card expiry month : ' . $paymentData['expiry_month'];
        } else {
            return null;
        }
    }

    /**
     * Get card expiry year
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getCardExpiryYear(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['expiry_year'] ?? null) {
            return 'Card expiry year : ' . $paymentData['expiry_year'];
        } else {
            return null;
        }
    }

    /**
     * Get bank holder name
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getIssuer(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['issuer'] ?? null) {
            return 'Card Bank : ' . $paymentData['issuer'];
        } else {
            return null;
        }
    }

    /**
     * Get bank holder country
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getIssuerCountry(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['issuer_country'] ?? null) {
            return 'Card Country : ' . $paymentData['issuer_country'];
        } else {
            return null;
        }
    }

    /**
     * Get mismatched adress fraud check
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getAvsCheck(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['avs_check'] ?? null) {
            return 'Mismatched Adress (fraud check) : ' . $paymentData['avs_check'];
        } else {
            return null;
        }
    }

    /**
     * Get product type
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getProductType(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? null;
        if ($paymentData['product_type'] ?? null) {
            return 'Payment Method refunded : ' . $paymentData['product_type'];
        } else {
            return null;
        }
    }

    /**
     * Get 3DS autorization code
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getThreeDsAuth(OrderInterface $order): ?string
    {
        $paymentData = $this->getThreeDs($order)['threeDs'] ?? null;
        if ($paymentData ?? null) {
            return '3DSecure success : ' . $paymentData['authentication_response'];
        } else {
            return null;
        }
    }

}
