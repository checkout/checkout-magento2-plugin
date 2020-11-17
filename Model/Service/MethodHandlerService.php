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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Service;

/**
 * Class MethodHandlerService.
 */
class MethodHandlerService
{
    /**
     * @var Array
     */
    public $instances;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @param MethodHandlerService constructor
     */
    public function __construct(
        $instances,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->instances = $instances;
        $this->orderHandler = $orderHandler;
        $this->customerSession = $customerSession;
    }

    public function get($methodId)
    {
        return $this->instances[$methodId];
    }

    /**
     * Retrieves the customers last APM payment source.
     */
    public function getPreviousSource()
    {
        // Get the customer id (currently logged in user)
        $customerId = $this->customerSession->getCustomer()->getId();
        
        if ($customerId) {
            // Find the order from increment id
            $order = $this->orderHandler->getOrder([
                'customer_id' => $customerId
            ]);

            if ($this->orderHandler->isOrder($order)) {
                if ($order->getPayment()->getAdditionalInformation('method_id') != null) {
                    return $order->getPayment()->getAdditionalInformation('method_id');
                } elseif ($order->getPayment()->getAdditionalInformation('public_hash') != null) {
                    return $order->getPayment()->getAdditionalInformation('public_hash');
                }
            }
        }
        
        return null;
    }

    /**
     * Retrieves the customers last used payment method.
     */
    public function getPreviousMethod()
    {
        // Get the customer id (currently logged in user)
        $customerId = $this->customerSession->getCustomer()->getId();
        
        if ($customerId) {
            // Find the order from increment id
            $order = $this->orderHandler->getOrder([
                'customer_id' => $customerId
            ]);

            if ($this->orderHandler->isOrder($order)) {
                if ($order->getPayment()->getMethod() !== null) {
                    return $order->getPayment()->getMethod();
                }
            }
        }
        
        return null;
    }
}
