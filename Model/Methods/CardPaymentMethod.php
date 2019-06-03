<?php

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Library\HttpHandler;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\BillingDescriptor;
class CardPaymentMethod extends Method
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
     * @var Session
     */
    protected $customerSession;

    /**
     * Constructor.
     *
     * @param      \Magento\Framework\Model\Context                         $context                 The context
     * @param      \Magento\Framework\Registry                              $registry                The registry
     * @param      \Magento\Framework\Api\ExtensionAttributesFactory        $extensionFactory        The extension factory
     * @param      \Magento\Framework\Api\AttributeValueFactory             $customAttributeFactory  The custom attribute factory
     * @param      \Magento\Payment\Helper\Data                             $paymentData             The payment data
     * @param      \Magento\Framework\App\Config\ScopeConfigInterface       $scopeConfig             The scope configuration
     * @param      \Magento\Payment\Model\Method\Logger                     $logger                  The logger
     * @param      \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress     $remoteAddress           The remote address
     * @param      \CheckoutCom\Magento2\Gateway\Config\Config              $config                  The configuration
     * @param      \CheckoutCom\Magento2\Model\Service\ApiHandlerService    $apiHandler              The api handler
     * @param      \CheckoutCom\Magento2\Model\Service\QuoteHandlerService  $quoteHandler            The quote handler
     * @param      \CheckoutCom\Magento2\Model\Service\CardHandlerService   $cardHandler             The card handler
     * @param      \Magento\Framework\Model\ResourceModel\AbstractResource  $resource                The resource
     * @param      \Magento\Framework\Data\Collection\AbstractDb            $resourceCollection      The resource collection
     * @param      array                                                    $data                    The data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \Magento\Customer\Model\Session $customerSession,
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
            $remoteAddress,
            $config,
            $apiHandler,
            $quoteHandler,
            $resource,
            $resourceCollection,
            $data
        );

        $this->cardHandler = $cardHandler;
        $this->customerSession = $customerSession;

    }

	/**
     * Send a charge request.
     */
    public function sendPaymentRequest($data, $amount, $currency, $reference = '') {

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
        if ($captureDate) {
            $request->capture_time = $this->config->getCaptureTime();
        }

        // Mada BIN Check
        if (isset($data['cardBin']) && $this->cardHandler->isMadaBin($data['cardBin']) && $madaEnabled) {
            $request->metadata['udf1'] = 'MADA';
        }

        // Save card check
        if (isset($data['saveCard']) && $saveCardEnabled && $this->customerSession->isLoggedIn()) {
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

    }

    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        // Check the status
        if (!$this->canVoid()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void action is not available.'));
        }

        // Process the void request
        $response = $this->apiHandler->voidTransaction($payment);
        if (!$this->apiHandler->isValidResponse($response)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void request could not be processed.'));
        }

        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Check the status
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        // Process the refund request
        $response = $this->apiHandler->refundTransaction($payment, $amount);
        if (!$this->apiHandler->isValidResponse($response)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund request could not be processed.'));
        }

        return $this;
    }

    /**
     * Check whether method is available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    // Todo - move this method to abstract class as it's needed for all payment methods
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        // If the quote is valid
        if (parent::isAvailable($quote) && null !== $quote) {
            // Filter by quote currency
            return in_array(
                $quote->getQuoteCurrencyCode(),
                explode(
                    ',',
                    $this->config->getValue('accepted_currencies')
                )
            ) && $this->config->getValue('active', $this->_code);
        }

        return false;
    }
}
