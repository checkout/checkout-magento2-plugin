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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Block\Adminhtml\Payment\Moto;
use \Checkout\Models\Payments\BillingDescriptor;
use \Checkout\Library\Exceptions\CheckoutHttpException;

/**
 * Class MotoMethod
 */
class MotoMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'checkoutcom_moto';

    /**
     * @var string
     */
    public $_code = self::CODE;

    /**
     * @var string
     */
    public $_formBlockType = Moto::class;

    /**
     * @var bool
     */
    public $_canAuthorize = true;

    /**
     * @var bool
     */
    public $_canCapture = true;

    /**
     * @var bool
     */
    public $_canCancel = true;

    /**
     * @var bool
     */
    public $_canCapturePartial = true;

    /**
     * @var bool
     */
    public $_canVoid = true;

    /**
     * @var bool
     */
    public $_canUseInternal = true;

    /**
     * @var bool
     */
    public $_canUseCheckout = true;

    /**
     * @var bool
     */
    public $_canRefund = true;

    /**
     * @var bool
     */
    public $_canRefundInvoicePartial = true;

    /**
     * @var Logger
     */
    public $ckoLogger;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * MotoMethod constructor.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\apiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Logger $ckoLogger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder         = $urlBuilder;
        $this->backendAuthSession = $backendAuthSession;
        $this->cart               = $cart;
        $this->_objectManager     = $objectManager;
        $this->invoiceSender      = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
        $this->checkoutData       = $checkoutData;
        $this->quoteRepository    = $quoteRepository;
        $this->quoteManagement    = $quoteManagement;
        $this->orderSender        = $orderSender;
        $this->sessionQuote       = $sessionQuote;
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
        $this->ckoLogger          = $ckoLogger;
    }

    /**
     * Perform a void request.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            if ($this->backendAuthSession->isLoggedIn()) {
                // Get the store code
                $storeCode = $payment->getOrder()->getStore()->getCode();

                // Initialize the API handler
                $api = $this->apiHandler->init($storeCode);

                // Check the status
                if (!$this->canVoid()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The void action is not available.')
                    );
                }

                // Process the void request
                $response = $api->voidOrder($payment);
                if (!$api->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The void request could not be processed.')
                    );
                }

                // Set the transaction id from response
                $payment->setTransactionId($response->action_id);
            }
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
        } finally {
            return $this;
        }
    }

    /**
     * Perform a capture request.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     * @param float $amount
     * 
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            if ($this->backendAuthSession->isLoggedIn()) {
                // Get the store code
                $storeCode = $payment->getOrder()->getStore()->getCode();

                // Initialize the API handler
                $api = $this->apiHandler->init($storeCode);

                // Check the status
                if (!$this->canCapture()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The capture action is not available.')
                    );
                }

                // Process the void request
                $response = $api->captureOrder($payment, $amount);
                if (!$api->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The capture request could not be processed.')
                    );
                }

                // Set the transaction id from response
                $payment->setTransactionId($response->action_id);
            }
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
        } finally {
            return $this;
        }
    }
    
    /**
     * Perform a refund request.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     * @param float $amount The amount
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            if ($this->backendAuthSession->isLoggedIn()) {
                // Get the store code
                $storeCode = $payment->getOrder()->getStore()->getCode();

                // Initialize the API handler
                $api = $this->apiHandler->init($storeCode);

                // Check the status
                if (!$this->canRefund()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund action is not available.')
                    );
                }

                // Process the refund request
                $response = $api->refundOrder($payment, $amount);
                if (!$api->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund request could not be processed.')
                    );
                }

                // Set the transaction id from response
                $payment->setTransactionId($response->action_id);
            }
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
        } finally {
            return $this;
        }
    }

    /**
     * Check whether method is enabled in config
     *
     * @param  \Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailableInConfig($quote = null)
    {
        return parent::isAvailable($quote);
    }

    /**
     * Check whether method is available
     *
     * @param  \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code)
            && $this->backendAuthSession->isLoggedIn();
        }

        return false;
    }
}
