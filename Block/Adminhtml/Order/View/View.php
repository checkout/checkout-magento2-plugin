<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Block\Adminhtml\Order\View;

use CheckoutCom\Magento2\Gateway\Config\Loader;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigAlternativePayments;
use CheckoutCom\Magento2\Model\Methods\AlternativePaymentMethod;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class View extends Template
{
    private Utilities $utilities;
    private Http $request;
    private OrderRepositoryInterface $orderRepository;
    private ConfigAlternativePayments $configAlternativePayments;
    private Loader $configLoader;

    public function __construct(
        Context $context,
        Utilities $utilities,
        Http $request,
        OrderRepositoryInterface $orderRepository,
        ConfigAlternativePayments $configAlternativePayments,
        Loader $configLoader,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->utilities = $utilities;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->configAlternativePayments = $configAlternativePayments;
        $this->configLoader = $configLoader;
    }

    public function getCkoPaymentData(string $data): ?string
    {
        $paymentData = $this->utilities->getPaymentData($this->getOrder(), 'cko_payment_information')['source'] ?? [];
        if (!empty($paymentData[$data])) {
            return (string)$paymentData[$data];
        }

        return null;
    }

    public function getCko3dsPaymentData(string $data): ?string
    {
        $paymentData = $this->utilities->getPaymentData($this->getOrder(), 'cko_threeDs')['threeDs'] ?? [];
        if (!empty($paymentData[$data])) {
            return $paymentData[$data];
        }

        return null;
    }

    private function getOrder(): OrderInterface
    {
        return $this->orderRepository->get($this->request->getParam('order_id'));
    }

    public function getAvsCheckDescription(string $avsCheckCode): string
    {
        switch ($avsCheckCode) {
            case 'A':
                return __('Street Match')->render();
            case 'B':
                return __('Street Match Postal Not Verified')->render();
            case 'I':
            case 'C':
                return __('Street and Postal Not Verified')->render();
            case 'F':
            case 'M':
            case 'D':
                return __('Street and Postal Match')->render();
            case 'G':
                return __('Not Verified or Not Supported')->render();
            case 'N':
                return __('No Address Match')->render();
            case 'P':
                return __('Street Not Verified Postal Match')->render();
            case 'R':
                return __('AVS Not Available')->render();
            case 'S':
                return __('Not supported')->render();
            case 'U':
                return __('Match Not Capable')->render();
            case 'Y':
                return __('Street and 5 Digit Postal Match')->render();
            case 'Z':
                return __('5 Digit Postal Match')->render();
            case 'AE1':
                return __('Cardholder Name Incorrect but Postal/ZIP Match')->render();
            case 'AE2':
                return __('Cardholder Name Incorrect, but Street and Postal/ZIP Match')->render();
            case 'AE3':
                return __('Cardholder Name Incorrect, but Street Match')->render();
            case 'AE4':
                return __('Cardholder Name Match')->render();
            case 'AE5':
                return __('Cardholder Name and Postal/ZIP Match')->render();
            case 'AE6':
                return __('Cardholder Name, Street and Postal/ZIP Match')->render();
            case 'AE7':
                return __('Cardholder Name and Street Match')->render();
            default:
                return '';
        }
    }

    public function getCvvCheckDescription(string $cvvCheckCode): string
    {
        switch ($cvvCheckCode) {
            case 'X':
                return __('No CVV2 information is available')->render();
            case 'U':
                return __('The issuer has not certified or has not provided the encryption keys to the interchange')->render();
            case 'P':
                return __('Card verification not performed, CVD was not on the card. Not all cards have a CVD value encoded')->render();
            case 'Y':
                return __('Card verification performed, and CVD was valid')->render();
            case 'D':
                return __('Card verification performed, and CVD was invalid')->render();
            case 'N':
                return __('Authorizing entity has not attempted card verification or could not verify the CVD due to a security device error')->render();
            default:
                return '';
        }
    }

    public function get3dsDescription(string $threeDsCode): string
    {
        switch ($threeDsCode) {
            case 'Y':
                return __('Authentication verification successful.')->render();
            case 'N':
                return __('Not authenticated or account not verified. This means the transaction was denied.')->render();
            case 'U':
                return __('Authentication or account verification could not be performed. This is due to a technical problem, or another problem as indicated in ARes or RReq.')->render();
            case 'A':
                return __('Attempt at processing performed. Not authenticated or verified, but a proof of attempted authentication/verification is provided.')->render();
            case 'C':
                return __('Challenge required. Additional authentication is required using the CReq or CRes.')->render();
            case 'D':
                return __('Challenge required. Decoupled authentication confirmed.')->render();
            case 'R':
                return __('Authentication or account verification rejected. Issuer is rejecting and requests that authorization not be attempted.')->render();
            case 'I':
                return __('Informational only. 3DS requestor challenge preference acknowledged.')->render();
            default:
                return '';
        }
    }

    public function getAlternativePaymentMethodName(): string
    {
        $methodId = $this->getOrder()->getPayment()->getAdditionalInformation()['method_id'] ?? '';
        return $methodId ?? $this->configLoader->getApmLabel($methodId)[$methodId];
    }

    public function getAlternativePaymentMethodTransactionInfo(): string
    {
        return $this->getOrder()->getPayment()->getAdditionalInformation()['transaction_info']['id'] ?? '';
    }
}
