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

use Checkoutcom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class View extends Template
{
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;

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
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * $orderRepository field
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    private $orderRepository;

    /**
     * @param Context $context
     * @param Utilities $utilities
     * @param ApiHandlerService $apiHandler
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Utilities $utilities,
        ApiHandlerService $apiHandler,
        StoreManagerInterface $storeManager,
        Http $request,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->utilities = $utilities;
        $this->apiHandler = $apiHandler;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get order payment id
     *
     * @return string $paymentId
     */
    public function getPaymentId(): string
    {
        $paymentId = $this->getOrder()->getPayment()->getId();

        return $paymentId;
    }

    /**
     * Get information about card used for payment
     *
     * @param string $paymentId
     *
     * @return string $response
     */
    public function getCardInformation($paymentId): string
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
     * @return string $sectionName
     */
    public function sectionName(): string
    {
        return "Payment Additional Information";
    }

    /**
     * Get order
     *
     * @return OrderInterface $order
     */
    public function getOrder(): OrderInterface
    {
        return $this->orderRepository->get($this->request->getParam('order_id'));
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
     * @return string|null
     */
    public function getCardType(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['card_type'])) {
            return 'Card type : ' . $paymentData['card_type'];
        }
        
        return null;
    }

    /**
     * Get card last four digits
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getFourDigits(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['last4'])) {
            return 'Card 4 last numbers : ' . $paymentData['last4'];
        } 
        
        return null;
    }

    /**
     * Get card expiry month
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getCardExpiryMonth(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['expiry_month'])) {
            return 'Card expiry month : ' . $paymentData['expiry_month'];
        }
        
        return null;
    }

    /**
     * Get card expiry year
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getCardExpiryYear(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['expiry_year'])) {
            return 'Card expiry year : ' . $paymentData['expiry_year'];
        }
        
        return null;
    }

    /**
     * Get bank holder name
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getIssuer(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['issuer'])) {
            return 'Card Bank : ' . $paymentData['issuer'];
        }
        
        return null;
    }

    /**
     * Get bank holder country
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getIssuerCountry(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['issuer_country'])) {
            return 'Card Country : ' . $paymentData['issuer_country'];
        } 
        
        return null;
    }

    /**
     * Get mismatched adress fraud check
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getAvsCheck(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['avs_check'])) {
            return 'Mismatched Adress (fraud check) : ' . $paymentData['avs_check'];
        } 
        
        return null;
    }

    /**
     * Get product type
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getProductType(OrderInterface $order): ?string
    {
        $paymentData = $this->getPaymentData($order)['source'] ?? [];
        if (!empty($paymentData['product_type'])) {
            return 'Payment Method refunded : ' . $paymentData['product_type'];
        } 
        
        return null;
    }

    /**
     * Get 3DS autorization code
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function getThreeDsAuth(OrderInterface $order): ?string
    {
        $paymentData = $this->getThreeDs($order)['threeDs'] ?? [];
        if (!empty($paymentData['authentication_response'])) {
            return '3DSecure success : ' . $paymentData['authentication_response'];
        } 

        return null;
    }
}
