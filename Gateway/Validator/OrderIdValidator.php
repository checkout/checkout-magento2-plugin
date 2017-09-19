<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use CheckoutCom\Magento2\Model\Validator\Rule;
use Magento\Sales\Model\OrderFactory;

class OrderIdValidator extends ResponseValidator {

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * OrderIdValidator constructor.
     * @param ResultInterfaceFactory $resultFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(ResultInterfaceFactory $resultFactory, OrderFactory $orderFactory) {
        parent::__construct($resultFactory);

        $this->orderFactory = $orderFactory;
    }

    /**
     * Returns the array of the rules.
     *
     * @return Rule[]
     */
    protected function rules() {
        return [
            new Rule('Order ID Exists', function(array $subject) {
                $response   = SubjectReader::readResponse($subject);
                $orderId    = $response['message']['trackId'];
                $order      = $this->orderFactory->create()->loadByIncrementId($orderId);

                return ! $order->isEmpty();
            }, __('Checkout.com track ID is not matching to any orders.') ),
        ];
    }
}