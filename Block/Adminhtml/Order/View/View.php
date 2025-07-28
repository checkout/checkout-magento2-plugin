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
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class View extends Template
{
    private Utilities $utilities;
    private Http $request;
    private OrderRepositoryInterface $orderRepository;
    private Loader $configLoader;

    public function __construct(
        Utilities $utilities,
        Http $request,
        OrderRepositoryInterface $orderRepository,
        Loader $configLoader,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->utilities = $utilities;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
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

    private function getOrder(): OrderInterface
    {
        return $this->orderRepository->get($this->request->getParam('order_id'));
    }

    public function getCko3dsPaymentData(string $data): ?string
    {
        $paymentData = $this->utilities->getPaymentData($this->getOrder(), 'cko_threeDs')['threeDs'] ?? [];
        if (!empty($paymentData[$data])) {
            return $paymentData[$data];
        }

        return null;
    }

    public function getAvsCheckDescription(string $avsCheckCode): string
    {
        return match ($avsCheckCode) {
            'A' => __('Street Match')->render(),
            'B' => __('Street Match Postal Not Verified')->render(),
            'I', 'C' => __('Street and Postal Not Verified')->render(),
            'F', 'M', 'D' => __('Street and Postal Match')->render(),
            'G' => __('Not Verified or Not Supported')->render(),
            'N' => __('No Address Match')->render(),
            'P' => __('Street Not Verified Postal Match')->render(),
            'R' => __('AVS Not Available')->render(),
            'S' => __('Not supported')->render(),
            'U' => __('Match Not Capable')->render(),
            'Y' => __('Street and 5 Digit Postal Match')->render(),
            'Z' => __('5 Digit Postal Match')->render(),
            'AE1' => __('Cardholder Name Incorrect but Postal/ZIP Match')->render(),
            'AE2' => __('Cardholder Name Incorrect, but Street and Postal/ZIP Match')->render(),
            'AE3' => __('Cardholder Name Incorrect, but Street Match')->render(),
            'AE4' => __('Cardholder Name Match')->render(),
            'AE5' => __('Cardholder Name and Postal/ZIP Match')->render(),
            'AE6' => __('Cardholder Name, Street and Postal/ZIP Match')->render(),
            'AE7' => __('Cardholder Name and Street Match')->render(),
            default => '',
        };
    }

    public function getCvvCheckDescription(string $cvvCheckCode): string
    {
        return match ($cvvCheckCode) {
            'X' => __('No CVV2 information is available')->render(),
            'U' => __('The issuer has not certified or has not provided the encryption keys to the interchange')->render(),
            'P' => __('Card verification not performed, CVD was not on the card. Not all cards have a CVD value encoded')->render(),
            'Y' => __('Card verification performed, and CVD was valid')->render(),
            'D' => __('Card verification performed, and CVD was invalid')->render(),
            'N' => __('Authorizing entity has not attempted card verification or could not verify the CVD due to a security device error')->render(),
            default => '',
        };
    }

    public function get3dsDescription(string $threeDsCode): string
    {
        return match ($threeDsCode) {
            'Y' => __('Authentication verification successful.')->render(),
            'N' => __('Not authenticated or account not verified. This means the transaction was denied.')->render(),
            'U' => __(
                'Authentication or account verification could not be performed. This is due to a technical problem, or another problem as indicated in ARes or RReq.'
            )->render(),
            'A' => __(
                'Attempt at processing performed. Not authenticated or verified, but a proof of attempted authentication/verification is provided.'
            )->render(),
            'C' => __('Challenge required. Additional authentication is required using the CReq or CRes.')->render(),
            'D' => __('Challenge required. Decoupled authentication confirmed.')->render(),
            'R' => __('Authentication or account verification rejected. Issuer is rejecting and requests that authorization not be attempted.')->render(),
            'I' => __('Informational only. 3DS requestor challenge preference acknowledged.')->render(),
            default => '',
        };
    }

    public function getAlternativePaymentMethodName(): string
    {
        $methodId = $this->getOrder()->getPayment()?->getAdditionalInformation()['method_id'] ?? '';

        return $methodId ?? $this->configLoader->getApmLabel($methodId)[$methodId];
    }

    public function getAlternativePaymentMethodTransactionInfo(): string
    {
        return $this->getOrder()->getPayment()?->getAdditionalInformation()['transaction_info']['id'] ?? '';
    }
}
