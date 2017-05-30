<?php

namespace CheckoutCom\Magento2\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use CheckoutCom\Magento2\Model\Validator\Rule;

class ResponseCodeStatusValidator extends ResponseValidator{

    const SOFT_DECLINE_CODE = 20000;

    const HARD_DECLINE_CODE = 30000;

    /**
     * Returns the array of the rules.
     *
     * @return Rule[]
     */
    protected function rules() {
       return [
            new Rule('Decline Code Status', function(array $subject, Rule $rule) {
               
                $response   = SubjectReader::readResponse($subject);
               
                if(array_key_exists('responseCode', $response)) {
                    $code = (int) $response['responseCode'];
                   
                    if( array_key_exists('responseMessage', $response) ){
                        $rule->setErrorMessage(__( $response['responseMessage'] ));
                    }
                    
                    return ! ($code >= self::SOFT_DECLINE_CODE AND $code < (self::HARD_DECLINE_CODE + 10000));
                }

                return true;
            }),
        ];
    }

}
