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

namespace CheckoutCom\Magento2\Api\Data;

/**
 * Interface used to set the API v3 response
 *
 */
interface PaymentResponseInterface
{
    /**
     * Constants for keys of data array.
     */
    const SUCCESS = 'success';
    /**
     * ORDER_ID constant
     *
     * @var string ORDER_ID
     */
    const ORDER_ID = 'order_id';
    /**
     * REDIRECT_URL constant
     *
     * @var string REDIRECT_URL
     */
    const REDIRECT_URL = 'redirect_url';
    /**
     * ERROR_MESSAGE constant
     *
     * @var string ERROR_MESSAGE
     */
    const ERROR_MESSAGE = 'error_message';

    /**
     * Get the success status
     *
     * @return boolean
     */
    public function getSuccess();

    /**
     * Get the order id
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Get the redirect url
     *
     * @return string
     */
    public function getRedirectUrl();

    /**
     * Get the error message
     *
     * @return string
     */
    public function getErrorMessage();

    /**
     * Set the success status
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentResponseInterface
     */
    public function setSuccess($success);

    /**
     * Set the order id
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentResponseInterface
     */
    public function setOrderId($orderId);

    /**
     * Set the redirect url
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentResponseInterface
     */
    public function setRedirectUrl($redirectUrl);

    /**
     * Set the error message
     *
     * @return \CheckoutCom\Magento2\Api\Data\PaymentResponseInterface
     */
    public function setErrorMessage($errorMessage);
}
