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

namespace CheckoutCom\Magento2\Model\Methods;

use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Models\Payments\BillingDescriptor;
use Checkout\Models\Payments\Payment;
use Checkout\Models\Payments\ThreeDs;
use Checkout\Models\Payments\TokenSource;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CardPaymentMethod
 */
class CardPaymentMethod extends AbstractMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    const CODE = 'checkoutcom_card_payment';
    /**
     * $_code field
     *
     * @var string $_code
     */
    protected $_code = self::CODE;
    /**
     * $_canAuthorize field
     *
     * @var bool $_canAuthorize
     */
    protected $_canAuthorize = true;
    /**
     * $_canCapture field
     *
     * @var bool $_canCapture
     */
    protected $_canCapture = true;
    /**
     * $_canCapturePartial field
     *
     * @var bool $_canCapturePartial
     */
    protected $_canCapturePartial = true;
    /**
     * $_canVoid field
     *
     * @var bool $_canVoid
     */
    protected $_canVoid = true;
    /**
     * $_canUseInternal field
     *
     * @var bool $_canUseInternal
     */
    protected $_canUseInternal = false;
    /**
     * $_canUseCheckout field
     *
     * @var bool $_canUseCheckout
     */
    protected $_canUseCheckout = true;
    /**
     * $_canRefund field
     *
     * @var bool $_canRefund
     */
    protected $_canRefund = true;
    /**
     * $_canRefundInvoicePartial field
     *
     * @var bool $_canRefundInvoicePartial
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $cardHandler field
     *
     * @var CardHandlerService $cardHandler
     */
    private $cardHandler;
    /**
     * $ckoLogger field
     *
     * @var Logger $ckoLogger
     */
    private $ckoLogger;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
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
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $customerSession field
     *
     * @var Session $customerSession
     */
    private $customerSession;
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    private $backendAuthSession;

    /**
     * CardPaymentMethod constructor
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param Session                    $backendAuthSession
     * @param CustomerModelSession       $customerSession
     * @param Config                     $config
     * @param ApiHandlerService          $apiHandler
     * @param Utilities                  $utilities
     * @param StoreManagerInterface      $storeManager
     * @param QuoteHandlerService        $quoteHandler
     * @param CardHandlerService         $cardHandler
     * @param LoggerHelper               $ckoLogger
     * @param DirectoryHelper            $directoryHelper
     * @param DataObjectFactory          $dataObjectFactory
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Session $backendAuthSession,
        CustomerModelSession $customerSession,
        Config $config,
        ApiHandlerService $apiHandler,
        Utilities $utilities,
        StoreManagerInterface $storeManager,
        QuoteHandlerService $quoteHandler,
        CardHandlerService $cardHandler,
        LoggerHelper $ckoLogger,
        DirectoryHelper $directoryHelper,
        DataObjectFactory $dataObjectFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $directoryHelper,
            $dataObjectFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->backendAuthSession = $backendAuthSession;
        $this->customerSession    = $customerSession;
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
        $this->utilities          = $utilities;
        $this->storeManager       = $storeManager;
        $this->quoteHandler       = $quoteHandler;
        $this->cardHandler        = $cardHandler;
        $this->ckoLogger          = $ckoLogger;
    }

    /**
     * Send a charge request
     *
     * @param string[]           $data
     * @param float              $amount
     * @param string             $currency
     * @param string             $reference
     * @param CartInterface|null $quote
     * @param bool|null          $isApiOrder
     * @param mixed|null         $customerId
     *
     * @return CheckoutHttpException|Exception|mixed|void
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendPaymentRequest(
        array $data,
        float $amount,
        string $currency,
        string $reference = '',
        CartInterface $quote = null,
        bool $isApiOrder = null,
        $customerId = null
    ) {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        if (!$quote) {
            // Get the quote
            $quote = $this->quoteHandler->getQuote();
        }

        // Set the token source
        $tokenSource                  = new TokenSource($data['cardToken']);
        $tokenSource->billing_address = $api->createBillingAddress($quote);

        // Set the payment
        $request = new Payment(
            $tokenSource, $currency
        );

        // Prepare the metadata array
        $request->metadata['methodId'] = $this->_code;

        // Prepare the capture setting
        $madaEnabled = $this->config->getValue('mada_enabled', $this->_code);
        if (isset($data['cardBin']) && $this->cardHandler->isMadaBin($data['cardBin']) && $madaEnabled) {
            $request->metadata['udf1'] = 'MADA';
        } else {
            $needsAutoCapture = $this->config->needsAutoCapture($this->_code);
            $request->capture = $needsAutoCapture;
            if ($needsAutoCapture) {
                $request->capture_on = $this->config->getCaptureTime($this->_code);
            }
        }

        // Prepare the save card setting
        $saveCardEnabled = $this->config->getValue('save_card_option', $this->_code);

        // Set the request parameters
        $request->amount               = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $request->reference            = $reference;
        $request->success_url          = $this->getSuccessUrl($data, $isApiOrder);
        $request->failure_url          = $this->getFailureUrl($data, $isApiOrder);
        $request->threeDs              = new ThreeDs($this->config->needs3ds($this->_code));
        $request->threeDs->attempt_n3d = (bool)$this->config->getValue('attempt_n3d', $this->_code);
        $request->description          = __('Payment request from %1', $this->config->getStoreName())->render();
        $request->customer             = $api->createCustomer($quote);
        $request->payment_type         = 'Regular';
        if (!$quote->getIsVirtual()) {
            $request->shipping = $api->createShippingAddress($quote);
        }

        // Save card check
        if ($isApiOrder) {
            if (isset($data['successUrl'])) {
                $request->metadata['successUrl'] = $this->getSuccessUrl($data);
            }

            if (isset($data['failureUrl'])) {
                $request->metadata['failureUrl'] = $this->getFailureUrl($data);
            }

            if (isset($data['saveCard']) && $data['saveCard'] === true && $saveCardEnabled) {
                $request->metadata['saveCard']   = 1;
                $request->metadata['customerId'] = $customerId;
            }
        } else {
            if (isset($data['saveCard']) && json_decode(
                                                $data['saveCard']
                                            ) === true && $saveCardEnabled && $this->customerSession->isLoggedIn()) {
                $request->metadata['saveCard']   = 1;
                $request->metadata['customerId'] = $this->customerSession->getCustomer()->getId();
            }
        }

        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $request->billing_descriptor = new BillingDescriptor(
                $this->config->getValue('descriptor_name'), $this->config->getValue('descriptor_city')
            );
        }

        // Add the quote metadata
        $request->metadata['quoteData'] = json_encode(
            $this->quoteHandler->getQuoteRequestData($quote)
        );

        // Add the base metadata
        $request->metadata = array_merge(
            $request->metadata,
            $this->apiHandler->getBaseMetadata()
        );

        $this->ckoLogger->additional($this->utilities->objectToArray($request), 'payment');

        // Send the charge request
        try {
            $response = $api->getCheckoutApi()->payments()->request($request);

            return $response;
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
            if ($isApiOrder) {
                return $e;
            }
        }
    }

    /**
     * Perform a capture request
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this|CardPaymentMethod
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canCapture()) {
                throw new LocalizedException(
                    __('The capture action is not available.')
                );
            }

            // Process the capture request
            $response = $api->captureOrder($payment, $amount);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The capture request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a void request
     *
     * @param InfoInterface $payment
     *
     * @return $this|CardPaymentMethod
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canVoid()) {
                throw new LocalizedException(
                    __('The void action is not available.')
                );
            }

            // Process the void request
            $response = $api->voidOrder($payment);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The void request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a void request on order cancel
     *
     * @param InfoInterface $payment
     *
     * @return $this|CardPaymentMethod
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $order     = $payment->getOrder();
            $storeCode = $order->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canVoid()) {
                throw new LocalizedException(
                    __('The void action is not available.')
                );
            }

            // Process the void request
            $response = $api->voidOrder($payment);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The void request could not be processed.')
                );
            }

            $comment = __(
                'Canceled order online, the voided amount is %1.',
                $order->formatPriceTxt($order->getGrandTotal())
            );
            $payment->setMessage($comment);
            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a refund request
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this|CardPaymentMethod
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canRefund()) {
                throw new LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund request
            $response = $api->refundOrder($payment, $amount);

            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The refund request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Check whether method is available
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        if ($this->isModuleActive() && parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code) && !$this->backendAuthSession->isLoggedIn();
        }

        return false;
    }
}
