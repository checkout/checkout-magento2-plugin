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
 * @copyright 2010-2020 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Api\Data;

/**
 * Interface used for payment request in API v3
 *
 */
interface PaymentRequestInterface
{
    /**
     * Constants for keys of data array.
     */
    const PAYMENT_TOKEN = 'payment_token';
    const PAYMENT_METHOD = 'payment_method';
    const QUOTE_ID = 'quote_id';
    const CARD_BIN = 'card_bin';
    const CARD_CVV = 'card_cvv';
    const PUBLIC_HASH = 'public_hash';
    const SAVE_CARD = 'save_card';
    const SUCCESS_URL = 'success_url';
    const FAILURE_URL = 'failure_url';
    
    /**
     * Get the payment token
     *
     * @return string
     */
    public function getPaymentToken();
    
    /**
     * Get the payment method
     *
     * @return string
     */
    public function getPaymentMethod();

    /**
     * Get the quote id
     *
     * @return int
     */
    public function getQuoteId();

    /**
     * Get the card bin
     *
     * @return int
     */
    public function getCardBin();

    /**
     * Get the card cvv
     *
     * @return int
     */
    public function getCardCvv();

    /**
     * Get the public hash
     *
     * @return string
     */
    public function getPublicHash();

    /**
     * Get the public hash
     *
     * @return bool
     */
    public function getSaveCard();

    /**
     * Get the success url
     *
     * @return string
     */
    public function getSuccessUrl();

    /**
     * Get the failure url
     *
     * @return string
     */
    public function getFailureUrl();

    /**
     * Set the payment token
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setPaymentToken($paymentToken);

    /**
     * Set the payment method
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * Set the quote id
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setQuoteId($quoteId);

    /**
     * Set the card bin
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setCardBin($cardBin);

    /**
     * Set the card cvv
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setCardCvv($cardCvv);

    /**
     * Set the public hash
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setPublicHash($publicHash);
    
    /**
     * Set the save card
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setSaveCard($saveCard);

    /**
     * Set the success url
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setSuccessUrl($successUrl);

    /**
     * Set the failure url
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
     */
    public function setFailureUrl($failureUrl);
}
