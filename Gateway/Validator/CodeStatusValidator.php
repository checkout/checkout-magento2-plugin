<?php

namespace CheckoutCom\Magento2\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use CheckoutCom\Magento2\Model\Validator\Rule;

class CodeStatusValidator extends ResponseValidator{

    const API_VALIDATION_CODE = 70000;

    const BUSINESS_VALIDATION_CODE = 80000;
    
    /**
     * Returns the array of the rules.
     *
     * @return Rule[]
     */
    protected function rules() {
        return [
            new Rule('Code Status', function(array $subject) {
                $response   = SubjectReader::readResponse($subject);
                $code       = (int) $response['message']['responseCode'];

                return $code < self::API_VALIDATION_CODE OR $code > (self::BUSINESS_VALIDATION_CODE + 10000);
            }, __('Checkout.com response code status error.' ) ),
        ];
    }
   
}
