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

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Block\Adminhtml\Payment\Moto;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;

/**
 * Class MotoMethod
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class MotoMethod extends AbstractMethod
{
    /**
     * CODE field
     *
     * @var string CODE
     */
    const CODE = 'checkoutcom_moto';
    /**
     * $_code field
     *
     * @var string $_code
     */
    public $_code = self::CODE;
    /**
     * $_formBlockType
     *
     * @var string $_formBlockType
     */
    public $_formBlockType = Moto::class;
    /**
     * $_canAuthorize
     *
     * @var bool $_canAuthorize
     */
    public $_canAuthorize = true;
    /**
     * $_canCapture field
     *
     * @var bool $_canCapture
     */
    public $_canCapture = true;
    /**
     * $_canCancel field
     *
     * @var bool $_canCancel
     */
    public $_canCancel = true;
    /**
     * $_canCapturePartial field
     *
     * @var bool $_canCapturePartial
     */
    public $_canCapturePartial = true;
    /**
     * $_canVoid field
     *
     * @var bool $_canVoid
     */
    public $_canVoid = true;
    /**
     * $_canUseInternal field
     *
     * @var bool $_canUseInternal
     */
    public $_canUseInternal = true;
    /**
     * $_canUseCheckout field
     *
     * @var bool $_canUseCheckout
     */
    public $_canUseCheckout = true;
    /**
     * $_canRefund field
     *
     * @var bool $_canRefund
     */
    public $_canRefund = true;
    /**
     * $_canRefundInvoicePartial field
     *
     * @var bool $_canRefundInvoicePartial
     */
    public $_canRefundInvoicePartial = true;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    public $apiHandler;
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    public $messageManager;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    private $backendAuthSession;

    /**
     * MotoMethod constructor
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param Session                    $backendAuthSession
     * @param Config                     $config
     * @param ApiHandlerService          $apiHandler
     * @param ManagerInterface           $messageManager
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     * @param DirectoryHelper            $directoryHelper
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
        Config $config,
        ApiHandlerService $apiHandler,
        ManagerInterface $messageManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directoryHelper
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
            $data,
            $directoryHelper
        );

        $this->backendAuthSession = $backendAuthSession;
        $this->config              = $config;
        $this->apiHandler         = $apiHandler;
        $this->messageManager     = $messageManager;
    }

    /**
     * Perform a void request
     *
     * @param InfoInterface $payment
     *
     * @return $this|MotoMethod
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment)
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
     * @return $this|MotoMethod
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $order = $payment->getOrder();
            // Get the store code
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
     * Perform a capture request
     *
     * @param InfoInterface $payment
     * @param float          $amount
     *
     * @return $this|MotoMethod
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
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
     * Perform a refund request
     *
     * @param InfoInterface $payment
     * @param float          $amount
     *
     * @return $this|MotoMethod
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
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
     * Check whether method is enabled in config
     *
     * @param null $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailableInConfig($quote = null)
    {
        return parent::isAvailable($quote);
    }

    /**
     * Check whether method is available
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code) && $this->backendAuthSession->isLoggedIn();
        }

        return false;
    }
}
