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
 * Class PaymentResponse
 * Used to set the API v3 response details
 */
class PaymentResponse extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \CheckoutCom\Magento2\Api\Data\PaymentResponseInterface
{
    /**
     * Get success
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->getData(self::SUCCESS);
    }

    /**
     * Get order id
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * get redirect url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getData(self::REDIRECT_URL);
    }

    /**
     * Add two numbers.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->getData(self::ERROR_MESSAGE);
    }

    /**
     * Set success
     *
     * @param $success
     * @return bool
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * Set order id
     *
     * @param $orderId
     * @return int
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Set redirect url
     *
     * @param $redirectUrl
     * @return string
     */
    public function setRedirectUrl($redirectUrl)
    {
        return $this->setData(self::REDIRECT_URL, $redirectUrl);
    }

    /**
     * Set error message
     *
     * @param $errorMessage
     * @return string
     */
    public function setErrorMessage($errorMessage)
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }
}
