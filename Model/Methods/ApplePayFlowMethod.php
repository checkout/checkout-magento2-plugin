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

use CheckoutCom\Magento2\Api\ApplePayInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Provider\FlowMethodSettings;
use CheckoutCom\Magento2\Provider\FlowPaymentMethodSettings;
use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Directory\Helper\Data;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Flow-based Apple Pay as a standalone Magento payment method (its own radio in the
 * payment list). Reuses all of FlowMethod's capture/void/refund logic; only the payment
 * method code differs so Magento treats it as a separate method.
 */
class ApplePayFlowMethod extends FlowMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    public const CODE = 'checkoutcom_flow_apple_pay';

    /**
     * $code field
     *
     * @var string $code
     */
    protected $code = self::CODE;

    private RequestInterface $request;

    public function __construct(
        Config $config,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentData $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Data $directoryHelper,
        DataObjectFactory $dataObjectFactory,
        Session $backendAuthSession,
        ApiHandlerService $apiHandler,
        StoreManagerInterface $storeManager,
        FlowMethodSettings $flowMethodSettings,
        FlowPaymentMethodSettings $flowPaymentMethodSettings,
        RequestInterface $request,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
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
            $backendAuthSession,
            $apiHandler,
            $storeManager,
            $flowMethodSettings,
            $flowPaymentMethodSettings,
            $resource,
            $resourceCollection,
            $data
        );

        $this->request = $request;
    }

    /**
     * Available only when Apple Pay is enabled (payment/checkoutcom_apple_pay/active) AND configured
     * to display outside the Flow (payment/checkoutcom_apple_pay/flow_standalone), in addition to the
     * Flow availability checks. When flow_standalone is off, Apple Pay is rendered inside the
     * "Pay with Checkout.com" method instead, so this standalone method must not appear.
     *  Available only when: Apple Pay is active, flow_standalone is enabled, flow_enabled_on_all_browsers
     *  is enabled, and the checkout placement flag is enabled.
     *  When flow_standalone is off, Apple Pay renders inside "Pay with Checkout.com" instead.
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (Exception $error) {
            $websiteCode = null;

            $this->logger->error(
                sprintf('%s: Unable to fetch store code or website code: %s', __METHOD__, $error->getMessage())
            );
        }

        $browserSupportsNativeFlowApplePay = (bool)$this->request->getParam(
            ApplePayInterface::FLOW_APPLE_PAY_IS_NATIVE_PARAM_NAME
        );

        return $this->flowPaymentMethodSettings->isApplePayEnabled($websiteCode, true)
            && $this->flowPaymentMethodSettings->isApplePayFlowStandalone($websiteCode)
            && $this->flowPaymentMethodSettings->shouldIncludeApplePayForFlowSession($websiteCode, $browserSupportsNativeFlowApplePay)
            && $this->flowPaymentMethodSettings->isApplePayEnabledOnCheckout($websiteCode);
    }
}
