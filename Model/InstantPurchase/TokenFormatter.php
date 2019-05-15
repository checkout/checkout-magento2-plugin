<?php

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use Magento\Vault\Api\Data\PaymentTokenInterface;

class TokenFormatter implements \Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface
{
    /**
     * @inheritdoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);

            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/json.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($jsonDetails, 1));

            $formatted = 'test';
            /*
            $formatted = sprintf(
                '%s: %s, %s: %s (%s: %02d/%04d)',
                __('Credit Card'),
                $ccType,
                __('ending'),
                $details['cc_last_4'],
                __('expires'),
                $details['cc_exp_month'],
                $details['cc_exp_year']
            );
            */
    
            return $formatted;

    }
}