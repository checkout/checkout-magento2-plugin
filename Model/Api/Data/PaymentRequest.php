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

namespace CheckoutCom\Magento2\Model\Api\Data;

/**
 * Class PaymentRequest
 * Used to retrieve details send to the V3 endpoint.
 */
class PaymentRequest extends \Magento\Framework\Api\AbstractSimpleObject implements
    \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface
{
    /**
     * Get payment token
     *
     * @return string|null
     */
    public function getPaymentToken()
    {
        return $this->_get(self::PAYMENT_TOKEN);
    }
    
    /**
     * Get payment method
     *
     * @return string|null
     */
    public function getPaymentMethod()
    {
        return $this->_get(self::PAYMENT_METHOD);
    }

    /**
     * Get quote id
     *
     * @return int|null
     */
    public function getQuoteId()
    {
        return $this->_get(self::QUOTE_ID);
    }

    /**
     * Get card bin
     *
     * @return int|null
     */
    public function getCardBin()
    {
        return $this->_get(self::CARD_BIN);
    }

    /**
     * Get card cvv
     *
     * @return int|null
     */
    public function getCardCvv()
    {
        return $this->_get(self::CARD_CVV);
    }

    /**
     * Get public hash
     *
     * @return string|null
     */
    public function getPublicHash()
    {
        return $this->_get(self::PUBLIC_HASH);
    }

    /**
     * Get save card
     *
     * @return bool|null
     */
    public function getSaveCard()
    {
        return $this->_get(self::SAVE_CARD);
    }
    
    /**
     * Get success url
     *
     * @return string|null
     */
    public function getSuccessUrl()
    {
        return $this->_get(self::SUCCESS_URL);
    }

    /**
     * Get failure url
     *
     * @return string|null
     */
    public function getFailureUrl()
    {
        return $this->_get(self::FAILURE_URL);
    }

    /**
     * Set payment token
     *
     * @param string $paymentToken
     * @return $this
     */
    public function setPaymentToken($paymentToken)
    {
        return $this->setData(self::PAYMENT_TOKEN, $paymentToken);
    }

    /**
     * Set payment method
     *
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, $paymentMethod);
    }
    
    /**
     * Set quote id
     *
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * Set card bin
     *
     * @param int $cardBin
     * @return $this
     */
    public function setCardBin($cardBin)
    {
        return $this->setData(self::CARD_BIN, $cardBin);
    }

    /**
     * Set card bin
     *
     * @param int $cardCvv
     * @return $this
     */
    public function setCardCvv($cardCvv)
    {
        return $this->setData(self::CARD_CVV, $cardCvv);
    }
    
    /**
     * Set public hash
     *
     * @param string $publicHash
     * @return $this
     */
    public function setPublicHash($publicHash)
    {
        return $this->setData(self::PUBLIC_HASH, $publicHash);
    }

    /**
     * Set save card
     *
     * @param string $saveCard
     * @return $this
     */
    public function setSaveCard($saveCard)
    {
        return $this->setData(self::SAVE_CARD, $saveCard);
    }

    /**
     * Set success url
     *
     * @param string $successUrl
     * @return $this
     */
    public function setSuccessUrl($successUrl)
    {
        return $this->setData(self::SUCCESS_URL, $successUrl);
    }

    /**
     * Set failure url
     *
     * @param string $failureUrl
     * @return $this
     */
    public function setFailureUrl($failureUrl)
    {
        return $this->setData(self::FAILURE_URL, $failureUrl);
    }
}
