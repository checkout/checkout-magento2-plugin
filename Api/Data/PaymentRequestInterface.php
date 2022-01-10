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
     *
     * @var string PAYMENT_TOKEN
     */
    const PAYMENT_TOKEN = 'payment_token';
    /**
     * PAYMENT_METHOD constant
     *
     * @var string PAYMENT_METHOD
     */
    const PAYMENT_METHOD = 'payment_method';
    /**
     * QUOTE_ID constant
     *
     * @var string QUOTE_ID
     */
    const QUOTE_ID = 'quote_id';
    /**
     * CARD_BIN constant
     *
     * @var string CARD_BIN
     */
    const CARD_BIN = 'card_bin';
    /**
     * CARD_CVV constant
     *
     * @var string CARD_CVV
     */
    const CARD_CVV = 'card_cvv';
    /**
     * PUBLIC_HASH constant
     *
     * @var string PUBLIC_HASH
     */
    const PUBLIC_HASH = 'public_hash';
    /**
     * SAVE_CARD constant
     *
     * @var string SAVE_CARD
     */
    const SAVE_CARD = 'save_card';
    /**
     * SUCCESS_URL constant
     *
     * @var string SUCCESS_URL
     */
    const SUCCESS_URL = 'success_url';
    /**
     * FAILURE_URL constant
     *
     * @var string FAILURE_URL
     */
    const FAILURE_URL = 'failure_url';

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
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setPaymentToken(string $paymentToken): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the payment method
     *
     * @param string $paymentMethod
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setPaymentMethod(string $paymentMethod): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the quote id
     *
     * @param string $quoteId
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setQuoteId(string $quoteId): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the card bin
     *
     * @param int $cardBin
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setCardBin(int $cardBin): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the card cvv
     *
     * @param int $cardCvv
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setCardCvv(int $cardCvv): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the public hash
     *
     * @param string $publicHash
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setPublicHash(string $publicHash): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the save card
     *
     * @param string $saveCard
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setSaveCard(string $saveCard): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the success url
     *
     * @param string $successUrl
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setSuccessUrl(string $successUrl): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;

    /**
     * Set the failure url
     *
     * @param string $failureUrl
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setFailureUrl(string $failureUrl): \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;
}
