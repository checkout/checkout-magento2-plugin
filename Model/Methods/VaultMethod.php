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

use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;
use \Checkout\Models\Payments\BillingDescriptor;
use \Checkout\Library\Exceptions\CheckoutHttpException;

/**
 * Class VaultMethod.
 */
class VaultMethod extends AbstractMethod
{
    /**
     * @var string
     */
    const CODE = 'checkoutcom_vault';

    /**
     * @var string
     */
    public $_code = self::CODE;

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
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * @var CardHandlerService
     */
    public $cardHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var Logger
     */
    public $ckoLogger;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * VaultMethod constructor.
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
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Helper\Logger $ckoLogger,
        \Magento\Framework\Message\ManagerInterface $messageManager,
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

        $this->backendAuthSession = $backendAuthSession;
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
        $this->utilities          = $utilities;
        $this->storeManager       = $storeManager;
        $this->vaultHandler       = $vaultHandler;
        $this->cardHandler        = $cardHandler;
        $this->quoteHandler       = $quoteHandler;
        $this->ckoLogger          = $ckoLogger;
        $this->messageManager     = $messageManager;
    }

    /**
     * Sends a payment request.
     *
     * @param array   $data      The data
     * @param integer $amount    The amount
     * @param string  $currency  The currency
     * @param string  $reference The reference
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function sendPaymentRequest(
        $data, 
        $amount,
        $currency,
        $reference = '',
        $quote = null,
        $isApiOrder = null,
        $customerId = null
    ) {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        // Get the quote
        $quote = $this->quoteHandler->getQuote();

        // Find the card token
        if (isset($isApiOrder) && isset($customerId)) {
            $card = $this->vaultHandler->getCardFromHash($data['publicHash'], $customerId);
        } else {
            $card = $this->vaultHandler->getCardFromHash($data['publicHash']);
        }

        // Set the token source
        $idSource = new IdSource($card->getGatewayToken());

        // Check CVV config
        if ($this->config->getValue('require_cvv', $this->_code)) {
            if (!isset($data['cvv']) || (int) $data['cvv'] == 0) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The CVV value is required.'));
            } else {
                $idSource->cvv = $data['cvv'];
            }
        }

        // Set the payment
        $request = new Payment(
            $idSource,
            $currency
        );

        // Prepare the metadata array
        $request->metadata['methodId'] = $this->_code;

        // Prepare the capture setting
        $needsAutoCapture = $this->config->needsAutoCapture($this->_code);
        $request->capture = $needsAutoCapture;
        if ($needsAutoCapture) {
            $request->capture_on = $this->config->getCaptureTime($this->_code);
        }

        // Prepare the MADA setting
        $madaEnabled = (bool) $this->config->getValue('mada_enabled', $this->_code);

        // Set the request parameters
        $request->amount = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $request->reference = $reference;
        $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
        $request->threeDs = new ThreeDs($this->config->needs3ds($this->_code));
        $request->threeDs->attempt_n3d = (bool) $this->config->getValue(
            'attempt_n3d',
            $this->_code
        );
        $request->description = __('Payment request from %1', $this->config->getStoreName())->getText();
        $request->payment_type = 'Regular';
        $request->shipping = $api->createShippingAddress($quote);

        // Mada BIN Check
        if (isset($data['cardBin'])
            && $this->cardHandler->isMadaBin($data['cardBin'])
            && $madaEnabled
        ) {
            $request->metadata = ['udf1' => 'MADA'];
        }

        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $request->billing_descriptor = new BillingDescriptor(
                $this->config->getValue('descriptor_name'),
                $this->config->getValue('descriptor_city')
            );
        }

        // Add the quote metadata
        $request->metadata['quoteData'] = json_encode($this->quoteHandler->getQuoteRequestData($quote));

        // Add the base metadata
        $request->metadata = array_merge(
            $request->metadata,
            $this->apiHandler->getBaseMetadata()
        );
        
        // Send the charge request
        try {
            $response = $api->checkoutApi
                ->payments()->request($request);

            return $response;
        } catch (CheckoutHttpException $e) {
            $this->ckoLogger->write($e->getBody());
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

            // Process the capture request
            $response = $api->captureOrder($payment, $amount);
            if (!$api->isValidResponse($response)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The capture request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);

            // Display a message
            $this->messageManager->addSuccessMessage(__(
                'Please reload the page to view the updated order information.'
            ));
        }

        return $this;
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
    
            // Display a message
            $this->messageManager->addSuccessMessage(__(
                'Please reload the page to view the updated order information.'
            ));
        }

        return $this;
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

            // Display a message
            $this->messageManager->addSuccessMessage(__(
                'Please reload the page to view the updated order information.'
            ));
        }

        return $this;
    }

    /**
     * Check whether method is available
     *
     * @param  \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return $this->config->getValue('active', $this->_code)
        && $this->vaultHandler->userHasCards()
        && !$this->backendAuthSession->isLoggedIn();
    }
}
