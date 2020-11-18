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

namespace CheckoutCom\Magento2\Api;

/**
 * Interface for API v3
 */
interface V3Interface
{
    /**
     * Set payment information and place order for a specified cart.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface $paymentRequest
     * @return \CheckoutCom\Magento2\Api\Data\PaymentResponseInterface
     */
    public function executeApiV3(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface $paymentRequest
    );

    /**
     * Set payment information and place order for a guest quote.
     *
     * @param \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface $paymentRequest
     * @return \CheckoutCom\Magento2\Api\Data\PaymentResponseInterface
     */
    public function executeGuestApiV3(
        \CheckoutCom\Magento2\Api\Data\PaymentRequestInterface $paymentRequest
    );
}
