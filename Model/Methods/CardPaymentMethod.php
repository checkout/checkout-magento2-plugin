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

use \Checkout\Library\HttpHandler;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\BillingDescriptor;

/**
 * Class CardPaymentMethod
 */
class CardPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    const CODE = 'checkoutcom_card_payment';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var CardHandlerService
     */
    protected $cardHandler;

    /**
     * @var Logger
     */
    protected $ckoLogger;

    /**
     * @var Session
     */
    protected $customerSession;
    
    /**
     * @var Session
     */
    protected $backendAuthSession;

    /**
     * CardPaymentMethod constructor.
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
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\apiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Logger $ckoLogger,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
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
        $this->remoteAddress      = $remoteAddress;
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
        $this->quoteHandler       = $quoteHandler;
        $this->cardHandler        = $cardHandler;
        $this->ckoLogger          = $ckoLogger;
    }

    /**
     * Send a charge request.
     */
    public function sendPaymentRequest($data, $amount, $currency, $reference = '')
    {
        try {
            // Set the token source
            $tokenSource = new TokenSource($data['cardToken']);

            // Set the payment
            $request = new Payment(
                $tokenSource,
                $currency
            );

            // Prepare the metadata array
            $request->metadata = ['methodId' => $this->_code];

            // Prepare the capture date setting
            $captureDate = $this->config->getCaptureTime($this->_code);

            // Prepare the MADA setting
            $madaEnabled = $this->config->getValue('mada_enabled', $this->_code);

            // Prepare the save card setting
            $saveCardEnabled = $this->config->getValue('save_card_option', $this->_code);

            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($this->_code);
            $request->amount = $amount*100;
            $request->reference = $reference;
            $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
            $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
            $request->threeDs = new ThreeDs($this->config->needs3ds($this->_code));
            $request->threeDs->attempt_n3d = (bool) $this->config->getValue('attempt_n3d', $this->_code);
            $request->description = __('Payment request from %1', $this->config->getStoreName());
            // Todo - add customer to the request
            //$request->customer = $this->apiHandler->createCustomer($this->quoteHandler->getQuote());
            $request->payment_ip = $this->remoteAddress->getRemoteAddress();
            $request->payment_type = 'Regular';
            if ($captureDate) {
                $request->capture_time = $this->config->getCaptureTime();
            }

            // Mada BIN Check
            if (isset($data['cardBin'])
                && $this->cardHandler->isMadaBin($data['cardBin'])
                && $madaEnabled
            ) {
                $request->metadata['udf1'] = 'MADA';
            }

            // Save card check
            if (isset($data['saveCard'])
                && $saveCardEnabled
                && $this->customerSession->isLoggedIn()
            ) {
                $request->metadata['saveCard'] = true;
                $request->metadata['customerId'] = $this->customerSession->getCustomer()->getId();
            }

            // Billing descriptor
            /*
            if ($this->config->needsDynamicDescriptor()) {
                $request->billing_descriptor = new BillingDescriptor(
                    $this->getValue('descriptor_name'),
                    $this->getValue('descriptor_city')
                );
            }
            */

            // Send the charge request
            $response = $this->apiHandler->checkoutApi
                ->payments()
                ->request($request);

            return $response;
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return null;
        }
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
                // Check the status
                if (!$this->canVoid()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The void action is not available.')
                    );
                }

                // Process the void request
                $response = $this->apiHandler->voidOrder($payment);
                if (!$this->apiHandler->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The void request could not be processed.')
                    );
                }

                // Set the transaction id from response
                $payment->setTransactionId($response->action_id);
            }
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
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
                // Check the status
                if (!$this->canRefund()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund action is not available.')
                    );
                }

                // Process the refund request
                $response = $this->apiHandler->refundOrder($payment, $amount);
                if (!$this->apiHandler->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund request could not be processed.')
                    );
                }

                // Set the transaction id from response
                $payment->setTransactionId($response->action_id);
            }
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
        } finally {
            return $this;
        }
    }

    /**
     * Check whether method is available
     *
     * @param  \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        try {
            if (parent::isAvailable($quote) && null !== $quote) {
                return $this->config->getValue('active', $this->_code)
                && !$this->backendAuthSession->isLoggedIn();
            }

            return false;
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return false;
        }
    }
}
