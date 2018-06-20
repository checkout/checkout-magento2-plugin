<?php

namespace CheckoutCom\Magento2\Plugin;

use Magento\Checkout\Model\PaymentInformationManagement as CheckoutPaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use CheckoutCom\Magento2\Model\MethodList;
use Psr\Log\LoggerInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class PaymentInformationManagement
 */
class PaymentInformationManagement
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var bool
     */
    private $checkMethods;

    /**
     * PaymentInformationManagement constructor.
     * @param CartManagementInterface $cartManagement
     * @param LoggerInterface $logger
     * @param MethodList $methodList
     * @param bool $checkMethods
     */
    public function __construct(
        ManagerInterface $messageManager,
        CartManagementInterface $cartManagement,
        LoggerInterface $logger,
        MethodList $methodList,
        $checkMethods = false
    ) {
        $this->messageManager = $messageManager;
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
        $this->methodList = $methodList;
        $this->checkMethods = $checkMethods;
    }

    /**
     * @param CheckoutPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws CouldNotSaveException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        CheckoutPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->checkMethods && !in_array($paymentMethod->getMethod(), $this->methodList->get())) {
            return $proceed($cartId, $paymentMethod, $billingAddress);
        }
        $subject->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
        try {
            return $this->cartManagement->placeOrder($cartId);
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
    }
}
