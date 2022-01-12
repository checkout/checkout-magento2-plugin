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

namespace CheckoutCom\Magento2\Model\Api\Data;

use CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class PaymentRequest
 * Used to retrieve details send to the V3 endpoint
 */
class PaymentRequest extends AbstractSimpleObject implements PaymentRequestInterface
{
    /**
     * {@inheritDoc}
     *
     * @return string|null
     */
    public function getPaymentToken(): ?string
    {
        return $this->_get(self::PAYMENT_TOKEN);
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->_get(self::PAYMENT_METHOD);
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     */
    public function getQuoteId(): ?string
    {
        return $this->_get(self::QUOTE_ID);
    }

    /**
     * {@inheritDoc}
     *
     * @return int|null
     */
    public function getCardBin(): ?int
    {
        return $this->_get(self::CARD_BIN);
    }

    /**
     * {@inheritDoc}
     *
     * @return int|null
     */
    public function getCardCvv(): ?int
    {
        return $this->_get(self::CARD_CVV);
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     */
    public function getPublicHash(): ?string
    {
        return $this->_get(self::PUBLIC_HASH);
    }

    /**
     * {@inheritDoc}
     *
     * @return bool|null
     */
    public function getSaveCard(): ?bool
    {
        return $this->_get(self::SAVE_CARD);
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     */
    public function getSuccessUrl(): ?string
    {
        return $this->_get(self::SUCCESS_URL);
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     */
    public function getFailureUrl(): ?string
    {
        return $this->_get(self::FAILURE_URL);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $paymentToken
     *
     * @return PaymentRequestInterface
     */
    public function setPaymentToken(string $paymentToken): PaymentRequestInterface
    {
        return $this->setData(self::PAYMENT_TOKEN, $paymentToken);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $paymentMethod
     *
     * @return PaymentRequestInterface
     */
    public function setPaymentMethod(string $paymentMethod): PaymentRequestInterface
    {
        return $this->setData(self::PAYMENT_METHOD, $paymentMethod);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $quoteId
     *
     * @return PaymentRequestInterface
     */
    public function setQuoteId(string $quoteId): PaymentRequestInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * {@inheritDoc}
     *
     * @param int $cardBin
     *
     * @return PaymentRequestInterface
     */
    public function setCardBin(int $cardBin): PaymentRequestInterface
    {
        return $this->setData(self::CARD_BIN, $cardBin);
    }

    /**
     * {@inheritDoc}
     *
     * @param int $cardCvv
     *
     * @return PaymentRequestInterface
     */
    public function setCardCvv(int $cardCvv): PaymentRequestInterface
    {
        return $this->setData(self::CARD_CVV, $cardCvv);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $publicHash
     *
     * @return PaymentRequestInterface
     */
    public function setPublicHash(string $publicHash): PaymentRequestInterface
    {
        return $this->setData(self::PUBLIC_HASH, $publicHash);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $saveCard
     *
     * @return PaymentRequestInterface
     */
    public function setSaveCard(string $saveCard): PaymentRequestInterface
    {
        return $this->setData(self::SAVE_CARD, $saveCard);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $successUrl
     *
     * @return PaymentRequestInterface
     */
    public function setSuccessUrl(string $successUrl): PaymentRequestInterface
    {
        return $this->setData(self::SUCCESS_URL, $successUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $failureUrl
     *
     * @return PaymentRequestInterface
     */
    public function setFailureUrl(string $failureUrl): PaymentRequestInterface
    {
        return $this->setData(self::FAILURE_URL, $failureUrl);
    }
}
