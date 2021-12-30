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

namespace CheckoutCom\Magento2\Model\Api\Data;

use CheckoutCom\Magento2\Api\Data\PaymentResponseInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class PaymentResponse
 * Used to set the API v3 response details
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class PaymentResponse extends AbstractExtensibleModel implements PaymentResponseInterface
{
    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->getData(self::SUCCESS);
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getData(self::REDIRECT_URL);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->getData(self::ERROR_MESSAGE);
    }

    /**
     * {@inheritDoc}
     *
     * @param $success
     *
     * @return PaymentResponse
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * {@inheritDoc}
     *
     * @param $orderId
     *
     * @return PaymentResponse
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * {@inheritDoc}
     *
     * @param $redirectUrl
     *
     * @return PaymentResponse
     */
    public function setRedirectUrl($redirectUrl)
    {
        return $this->setData(self::REDIRECT_URL, $redirectUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @param $errorMessage
     *
     * @return PaymentResponse
     */
    public function setErrorMessage($errorMessage)
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }
}
