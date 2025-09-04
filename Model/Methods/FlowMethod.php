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

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;

class FlowMethod extends AbstractMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    public const CODE = 'checkoutcom_flow';

    /**
     * $code field
     *
     * @var string $code
     */
    protected $code = self::CODE;

    public function __construct(
        Config $config,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        DirectoryHelper $directoryHelper,
        DataObjectFactory $dataObjectFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $directoryHelper,
            $scopeConfig,
            $logger,
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $dataObjectFactory,
            $resource,
            $resourceCollection,
            $data
        );
        
    }

    public function sendPaymentRequest(
        array $data,
        float $amount,
        string $currency,
        string $reference = '',
        ?CartInterface $quote = null,
        ?bool $isApiOrder = null,
        $customerId = null
    ): array {

        return [];
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        if (!$quote) {
            // Get the quote
            $quote = $this->quoteHandler->getQuote();
        }

        // Set the token source
        $tokenSource = new RequestTokenSource();

        $tokenSource->token = $data['cardToken'];
        $tokenSource->billing_address = $api->createBillingAddress($quote);

        // Set the payment
        $request = new PaymentRequest();

        $request->currency = $currency;
        $request->source = $tokenSource;
        $request->processing_channel_id = $this->config->getValue('channel_id');

        // Prepare the metadata array
        $request->metadata['methodId'] = $this->code;

        // Prepare the capture setting
        $madaEnabled = $this->config->getValue('mada_enabled', $this->code);

        if (isset($data['cardBin']) && $this->cardHandler->isMadaBin($data['cardBin']) && $madaEnabled) {
            $request->metadata['udf1'] = 'MADA';
        } else {
            $needsAutoCapture = $this->config->needsAutoCapture();
            $request->capture = $needsAutoCapture;
            if ($needsAutoCapture) {
                $request->capture_on = $this->config->getCaptureTime();
            }
        }

        // Prepare the save card setting
        $saveCardEnabled = $this->config->getValue('save_card_option', $this->code);

        // Set the request parameters
        $request->amount = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $request->reference = $reference;
        $request->success_url = $this->getSuccessUrl($data, $isApiOrder);
        $request->failure_url = $this->getFailureUrl($data, $isApiOrder);

        $theeDsRequest = new ThreeDsRequest();
        $theeDsRequest->enabled = $this->config->needs3ds($this->code);
        $theeDsRequest->attempt_n3d = (bool)$this->config->getValue('attempt_n3d', $this->code);

        $request->three_ds = $theeDsRequest;
        $request->description = __('Payment request from %1', $this->config->getStoreName())->render();
        $request->customer = $api->createCustomer($quote);
        $request->payment_type = 'Regular';

        if (!$quote->getIsVirtual()) {
            $request->shipping = $api->createShippingAddress($quote);
        }

        // Preferred scheme
        if (isset($data['preferredScheme']) && in_array((string)strtoupper($data['preferredScheme']), self::PREFERRED_SCHEMES)) {
            $request->processing = ['preferred_scheme' => strtolower($data['preferredScheme'])];
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
                $request->metadata['saveCard'] = 1;
                $request->metadata['customerId'] = $customerId;
            }
        } else {
            if (isset($data['saveCard']) && $this->json->unserialize($data['saveCard']) === true && $saveCardEnabled && $this->customerSession->isLoggedIn()) {
                $request->metadata['saveCard'] = 1;
                $request->metadata['customerId'] = $this->customerSession->getCustomer()->getId();
            }
        }

        // Billing descriptor
        if ($this->config->needsDynamicDescriptor()) {
            $billingDescriptor = new BillingDescriptor();
            $billingDescriptor->city = $this->config->getValue('descriptor_city');
            $billingDescriptor->name = $this->config->getValue('descriptor_name', null, null, ScopeInterface::SCOPE_STORE);

            $request->billing_descriptor = $billingDescriptor;
        }

        // Add the quote metadata
        $request->metadata['quoteData'] = $this->json->serialize(
            $this->quoteHandler->getQuoteRequestData($quote)
        );

        // Add the base metadata
        $request->metadata = array_merge(
            $request->metadata,
            $this->apiHandler->getBaseMetadata()
        );

        $this->ckoLogger->additional($this->utilities->objectToArray($request), 'payment');

        // Send the charge request
        return $api->getCheckoutApi()->getPaymentsClient()->requestPayment($request);
    }
    
    /**
     * Check whether method is available
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        return true;
    }
}
