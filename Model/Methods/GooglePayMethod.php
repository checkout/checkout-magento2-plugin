<?php

/**
 * Checkout.com
 * Authorised and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

class GooglePayMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_google_pay';

    /**
     * @var string
     * @overriden
     */
    protected $_code = self::CODE;

    /**
     * Void.
     *
     * @param      \Magento\Payment\Model\InfoInterface             $payment  The payment
     *
     * @throws     \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return     self                                             ( description_of_the_return_value )
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        // Check the status
        if (!$this->canVoid()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void action is not available.'));
        }

        // Process the void request
        $response = $this->apiHandler->voidTransaction($payment);
        if (!$this->apiHandler->isValidResponse($response)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void request could not be processed.'));
        }

        return $this;
    }

    /**
     * Refund.
     *
     * @param      \Magento\Payment\Model\InfoInterface             $payment  The payment
     * @param      <type>                                           $amount   The amount
     *
     * @throws     \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return     self                                             ( description_of_the_return_value )
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Check the status
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        // Process the refund request
        $response = $this->apiHandler->refundTransaction($payment, $amount);
        if (!$this->apiHandler->isValidResponse($response)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund request could not be processed.'));
        }

        return $this;
    }

    /**
     * Check whether method is available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    // Todo - move this method to abstract class as it's needed for all payment methods
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        // If the quote is valid
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code);
        }

        return false;
    }
}
