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

namespace CheckoutCom\Magento2\Model\Vault;

use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Zend_Json;

/**
 * Class VaultToken
 */
class VaultToken
{
    /**
     * $paymentTokenFactory field
     *
     * @var PaymentTokenFactoryInterface $paymentTokenFactory
     */
    private $paymentTokenFactory;
    /**
     * $encryptor field
     *
     * @var EncryptorInterface $encryptor
     */
    private $encryptor;
    /**
     * $cardHandler field
     *
     * @var CardHandlerService $cardHandler
     */
    private $cardHandler;

    /**
     * VaultToken constructor
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param EncryptorInterface           $encryptor
     * @param CardHandlerService           $cardHandler
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        EncryptorInterface $encryptor,
        CardHandlerService $cardHandler
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->encryptor           = $encryptor;
        $this->cardHandler         = $cardHandler;
    }

    /**
     * Returns the prepared payment token.
     *
     * @param mixed[]         $card
     * @param string          $methodId
     * @param int|string|null $customerId
     *
     * @return PaymentTokenInterface
     * @throws Exception
     */
    public function create(array $card, string $methodId, $customerId = null): PaymentTokenInterface
    {
        $expiryMonth = str_pad((string)$card['expiry_month'], 2, '0', STR_PAD_LEFT);
        $expiryYear  = $card['expiry_year'];
        $expiresAt   = $this->getExpirationDate($expiryMonth, $expiryYear);
        $cardScheme  = $card['scheme'];

        /**
         * @var PaymentTokenInterface $paymentToken
         */
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setExpiresAt($expiresAt);

        if (array_key_exists('id', $card)) {
            $paymentToken->setGatewayToken($card['id']);
        }

        $tokenDetails = [
            'type'           => $this->cardHandler->getCardCode($cardScheme),
            'maskedCC'       => $card['last4'],
            'expirationDate' => $expiryMonth . '/' . $expiryYear,
        ];

        $paymentToken->setTokenDetails($this->convertDetailsToJSON($tokenDetails));
        $paymentToken->setIsActive(true);
        $paymentToken->setPaymentMethodCode($methodId);

        if ($customerId) {
            $paymentToken->setCustomerId($customerId);
        }

        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));

        return $paymentToken;
    }

    /**
     * Returns the date time object with the given expiration month and year.
     *
     * @param string|int $expiryMonth
     * @param string|int $expiryYear
     *
     * @return string
     * @throws Exception
     */
    private function getExpirationDate($expiryMonth, $expiryYear): string
    {
        $expDate = new DateTime(
            $expiryYear . '-' . $expiryMonth . '-' . '01' . ' ' . '00:00:00', new DateTimeZone('UTC')
        );

        return $expDate->add(new DateInterval('P1M'))->format('Y-m-d 00:00:00');
    }

    /**
     * Generate vault payment public hash
     *
     * @param PaymentTokenInterface $paymentToken
     *
     * @return string
     */
    private function generatePublicHash(PaymentTokenInterface $paymentToken): string
    {
        $hashKey = $paymentToken->getGatewayToken();

        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode() . $paymentToken->getType() . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * Returns the JSON object of the given data.
     *
     * Convert payment token details to JSON
     *
     * @param mixed[] $details
     *
     * @return string
     */
    private function convertDetailsToJSON(array $details): string
    {
        $json = Zend_Json::encode($details);

        return $json ?: '{}';
    }
}
