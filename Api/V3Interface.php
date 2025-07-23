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

namespace CheckoutCom\Magento2\Api;

use CheckoutCom\Magento2\Api\Data\PaymentRequestInterface;
use CheckoutCom\Magento2\Api\Data\PaymentResponseInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Interface for API v3
 */
interface V3Interface
{
    /**
     * Set payment information and place order for a specified cart.
     *
     * @param CustomerInterface $customer
     * @param PaymentRequestInterface $paymentRequest
     *
     * @return PaymentResponseInterface
     */
    public function executeApiV3(
        CustomerInterface $customer,
        PaymentRequestInterface $paymentRequest
    ): PaymentResponseInterface;

    /**
     * Set payment information and place order for a guest quote.
     *
     * @param PaymentRequestInterface $paymentRequest
     *
     * @return PaymentResponseInterface
     */
    public function executeGuestApiV3(
        PaymentRequestInterface $paymentRequest
    ): PaymentResponseInterface;
}
