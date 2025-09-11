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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Methods;

use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Provider\FlowMethodSettings;
use Magento\Backend\Model\Auth\Session;
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
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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

    private Session $backendAuthSession;
    private Config $config;
    private ApiHandlerService $apiHandler;
    private StoreManagerInterface $storeManager;
    private FlowMethodSettings $flowMethodSettings;

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
        Session $backendAuthSession,
        ApiHandlerService $apiHandler,
        StoreManagerInterface $storeManager,
        FlowMethodSettings $flowMethodSettings,
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

        $this->backendAuthSession = $backendAuthSession;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->storeManager = $storeManager;
        $this->flowMethodSettings = $flowMethodSettings;
    }

    /**
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment): AbstractMethod
    {
        if (!$this->backendAuthSession->isLoggedIn()) {
            return $this;
        }

        // Get the store code
        $storeCode = $payment->getOrder()->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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
        $payment->setTransactionId($response['action_id']);

        return $this;
    }

    /**
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment): AbstractMethod
    {
        if (!$this->backendAuthSession->isLoggedIn()) {
            return $this;
        }

        $order = $payment->getOrder();
        // Get the store code
        $storeCode = $order->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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
        $payment->setTransactionId($response['action_id']);

        return $this;
    }

    /**
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function capture(InfoInterface $payment, $amount): AbstractMethod
    {
        if (!$this->backendAuthSession->isLoggedIn()) {
            return $this;
        }
        // Get the store code
        $storeCode = $payment->getOrder()->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        // Check the status
        if (!$this->canCapture()) {
            throw new LocalizedException(
                __('The capture action is not available.')
            );
        }

        // Process the capture request
        $response = $api->captureOrder($payment, (float)$amount);
        if (!$api->isValidResponse($response)) {
            throw new LocalizedException(
                __('The capture request could not be processed.')
            );
        }

        // Set the transaction id from response
        $payment->setTransactionId($response['action_id']);

        return $this;
    }

    /**
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function refund(InfoInterface $payment, $amount): AbstractMethod
    {
        if (!$this->backendAuthSession->isLoggedIn()) {
            return $this;
        }
        // Get the store code
        $storeCode = $payment->getOrder()->getStore()->getCode();

        // Initialize the API handler
        try {
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
        } catch (CheckoutArgumentException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        // Check the status
        if (!$this->canRefund()) {
            throw new LocalizedException(
                __('The refund action is not available.')
            );
        }

        // Process the refund request
        try {
            $response = $api->refundOrder($payment, $amount);
        } catch (CheckoutApiException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        if (!$api->isValidResponse($response)) {
            throw new LocalizedException(
                __('The refund request could not be processed.')
            );
        }

        // Set the transaction id from response
        $payment->setTransactionId($response['action_id']);

        return $this;
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
    }

    /**
     * @throws LocalizedException
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        if (null !== $quote && $this->isModuleActive() && parent::isAvailable($quote)) {
            $websiteCode = $this->storeManager->getWebsite()->getCode();

            return $this->flowMethodSettings->isAvailable($websiteCode);
        }

        return false;
    }
}
