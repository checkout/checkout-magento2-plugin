<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Factory;

use Magento\Vault\Api\Data\PaymentTokenInterface;

class VaultTokenFactory {

    /**
     * @var CreditCardTokenFactory
     */
    protected $creditCardTokenFactory;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var CardHandlerService
     */
    protected $cardHandler;

    /**
     * @var Logger
     */
    protected $logger;
    
    /**
     * VaultTokenFactory constructor.
     */
    public function __construct(
        \Magento\Vault\Model\CreditCardTokenFactory $creditCardTokenFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->creditCardTokenFactory = $creditCardTokenFactory;
        $this->encryptor = $encryptor;
        $this->cardHandler = $cardHandler;
        $this->logger = $logger;
    }

    /**
     * Returns the prepared payment token.
     *
     * @param array $card
     * @param int|null $customerId
     * @return PaymentTokenInterface
     */
    public function create(array $card, $methodId, $customerId = null) {
        try {
            $expiryMonth    = str_pad($card['expiry_month'], 2, '0', STR_PAD_LEFT);
            $expiryYear     = $card['expiry_year'];
            $expiresAt      = $this->getExpirationDate($expiryMonth, $expiryYear);
            $cardScheme      = $card['scheme'];

            /** @var PaymentTokenInterface $paymentToken */
            $paymentToken = $this->creditCardTokenFactory->create();
            $paymentToken->setExpiresAt($expiresAt);

            if (array_key_exists('id', $card) ) {
                $paymentToken->setGatewayToken($card['id']);
            }

            $tokenDetails = [
                'type'              => $this->cardHandler->getCardCode($cardScheme),
                'maskedCC'          => $card['last4'],
                'expirationDate'    => $expiryMonth . '/' . $expiryYear,
            ];

            $paymentToken->setTokenDetails($this->convertDetailsToJSON($tokenDetails));
            $paymentToken->setIsActive(true);
            $paymentToken->setPaymentMethodCode($methodId);

            if ($customerId) {
                $paymentToken->setCustomerId($customerId);
            }

            $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));

            return $paymentToken;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }   
    }

    /**
     * Returns the date time object with the given expiration month and year.
     *
     * @param string $expiryMonth
     * @param string $expiryYear
     * @return string
     */
    private function getExpirationDate($expiryMonth, $expiryYear) {
        try {
            $expDate = new \DateTime(
                $expiryYear
                . '-'
                . $expiryMonth
                . '-'
                . '01'
                . ' '
                . '00:00:00',
                new \DateTimeZone('UTC')
            );

            return $expDate->add(new \DateInterval('P1M'))->format('Y-m-d 00:00:00');
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }   
    }

    /**
    * Generate vault payment public hash
    *
    * @param PaymentTokenInterface $paymentToken
    * @return string
    */
    private function generatePublicHash(PaymentTokenInterface $paymentToken) {
        try {
            $hashKey = $paymentToken->getGatewayToken();

            if ($paymentToken->getCustomerId()) {
                $hashKey = $paymentToken->getCustomerId();
            }

            $hashKey .= $paymentToken->getPaymentMethodCode()
                . $paymentToken->getType()
                . $paymentToken->getTokenDetails();

            return $this->encryptor->getHash($hashKey);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }   
    }

    /**
     * Returns the JSON object of the given data.
     *
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON(array $details) {
        $json = \Zend_Json::encode($details);
        return $json ?: '{}';
    }
}
