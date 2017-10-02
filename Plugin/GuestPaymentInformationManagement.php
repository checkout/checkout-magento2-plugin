<?php

namespace CheckoutCom\Magento2\Plugin;

use Magento\Checkout\Model\GuestPaymentInformationManagement as CheckoutGuestPaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\GuestCartManagementInterface;
use Psr\Log\LoggerInterface;
use CheckoutCom\Magento2\Model\MethodList;

/**
 * Class GuestPaymentInformationManagement
 */
class GuestPaymentInformationManagement
{
    /**
     * @var GuestCartManagementInterface
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
     * GuestPaymentInformationManagement constructor.
     * @param GuestCartManagementInterface $cartManagement
     * @param LoggerInterface $logger
     * @param MethodList $methodList
     * @param bool $checkMethods
     */
    public function __construct(
        GuestCartManagementInterface $cartManagement,
        LoggerInterface $logger,
        MethodList $methodList,
        $checkMethods = false
    ) {
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
        $this->methodList = $methodList;
        $this->checkMethods = $checkMethods;
    }

    /**
     * @param CheckoutGuestPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param $email
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws CouldNotSaveException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        CheckoutGuestPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->checkMethods && !in_array($paymentMethod->getMethod(), $this->methodList->get())) {
            return $proceed($cartId, $email, $paymentMethod, $billingAddress);
        }
        $subject->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
        try {
            $orderId = $this->cartManagement->placeOrder($cartId);
        } catch (LocalizedException $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'),
                $exception
            );
        }
        return $orderId;
    }
}
