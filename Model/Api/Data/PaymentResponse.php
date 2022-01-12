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

use CheckoutCom\Magento2\Api\Data\PaymentResponseInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class PaymentResponse
 * Used to set the API v3 response details
 */
class PaymentResponse extends AbstractExtensibleModel implements PaymentResponseInterface
{
    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->getData(self::SUCCESS);
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->getData(self::REDIRECT_URL);
    }

    /**
     * {@inheritDoc}
     *
     * @return string|array
     */
    public function getErrorMessage()
    {
        return $this->getData(self::ERROR_MESSAGE);
    }

    /**
     * {@inheritDoc}
     *
     * @param bool $success
     *
     * @return PaymentResponseInterface
     */
    public function setSuccess(bool $success): PaymentResponseInterface
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * {@inheritDoc}
     *
     * @param int $orderId
     *
     * @return PaymentResponseInterface
     */
    public function setOrderId(int $orderId): PaymentResponseInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $redirectUrl
     *
     * @return PaymentResponseInterface
     */
    public function setRedirectUrl(string $redirectUrl): PaymentResponseInterface
    {
        return $this->setData(self::REDIRECT_URL, $redirectUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|array $errorMessage
     *
     * @return PaymentResponseInterface
     */
    public function setErrorMessage($errorMessage): PaymentResponseInterface
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }
}
