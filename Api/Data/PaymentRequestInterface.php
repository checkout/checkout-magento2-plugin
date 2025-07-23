<?php
/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Api\Data;

/**
 * Interface used for payment request in API v3
 */
interface PaymentRequestInterface
{
    /**
     * Constants for keys of data array.
     */
    /**
     * PAYMENT_TOKEN constant
     */
    public const string PAYMENT_TOKEN = 'payment_token';
    /**
     * PAYMENT_METHOD constant
     */
    public const string PAYMENT_METHOD = 'payment_method';
    /**
     * QUOTE_ID constant
     */
    public const string QUOTE_ID = 'quote_id';
    /**
     * CARD_BIN constant
     */
    public const string CARD_BIN = 'card_bin';
    /**
     * CARD_CVV constant
     */
    public const string CARD_CVV = 'card_cvv';
    /**
     * PUBLIC_HASH constant
     */
    public const string PUBLIC_HASH = 'public_hash';
    /**
     * SAVE_CARD constant
     */
    public const string SAVE_CARD = 'save_card';
    /**
     * SUCCESS_URL constant
     */
    public const string SUCCESS_URL = 'success_url';
    /**
     * FAILURE_URL constant
     */
    public const string FAILURE_URL = 'failure_url';

    /**
     * Get the payment token
     *
     * @return string|null
     */
    public function getPaymentToken(): ?string;

    /**
     * Get the payment method
     *
     * @return string|null
     */
    public function getPaymentMethod(): ?string;

    /**
     * Get the quote id
     *
     * @return string|null
     */
    public function getQuoteId(): ?string;

    /**
     * Get the card bin
     *
     * @return int|null
     */
    public function getCardBin(): ?int;

    /**
     * Get the card cvv
     *
     * @return int|null
     */
    public function getCardCvv(): ?int;

    /**
     * Get the public hash
     *
     * @return string|null
     */
    public function getPublicHash(): ?string;

    /**
     * Get the public hash
     *
     * @return bool|null
     */
    public function getSaveCard(): ?bool;

    /**
     * Get the success url
     *
     * @return string|null
     */
    public function getSuccessUrl(): ?string;

    /**
     * Get the failure url
     *
     * @return string|null
     */
    public function getFailureUrl(): ?string;

    /**
     * Set the payment token
     *
     * @param string $paymentToken
     *
     * @return PaymentRequestInterface
     */
    public function setPaymentToken(string $paymentToken): PaymentRequestInterface;

    /**
     * Set the payment method
     *
     * @param string $paymentMethod
     *
     * @return PaymentRequestInterface
     */
    public function setPaymentMethod(string $paymentMethod): PaymentRequestInterface;

    /**
     * Set the quote id
     *
     * @param string $quoteId
     *
     * @return PaymentRequestInterface
     */
    public function setQuoteId(string $quoteId): PaymentRequestInterface;

    /**
     * Set the card bin
     *
     * @param int $cardBin
     *
     * @return PaymentRequestInterface
     */
    public function setCardBin(int $cardBin): PaymentRequestInterface;

    /**
     * Set the card cvv
     *
     * @param int $cardCvv
     *
     * @return PaymentRequestInterface
     */
    public function setCardCvv(int $cardCvv): PaymentRequestInterface;

    /**
     * Set the public hash
     *
     * @param string $publicHash
     *
     * @return PaymentRequestInterface
     */
    public function setPublicHash(string $publicHash): PaymentRequestInterface;

    /**
     * Set the save card
     *
     * @param string $saveCard
     *
     * @return PaymentRequestInterface
     */
    public function setSaveCard(string $saveCard): PaymentRequestInterface;

    /**
     * Set the success url
     *
     * @param string $successUrl
     *
     * @return PaymentRequestInterface
     */
    public function setSuccessUrl(string $successUrl): PaymentRequestInterface;

    /**
     * Set the failure url
     *
     * @param string $failureUrl
     *
     * @return PaymentRequestInterface
     */
    public function setFailureUrl(string $failureUrl): PaymentRequestInterface;
}
