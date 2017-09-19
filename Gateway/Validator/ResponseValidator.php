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

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use CheckoutCom\Magento2\Model\Validator\Rule;

abstract class ResponseValidator extends AbstractValidator {

    /**
     * If true and the first validation will failed then stop processing and return the first error message.
     *
     * @var bool
     */
    protected $stopInFirstError = false;

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject) {
        
        $isValid        = true;
        $errorMessages  = [];

        foreach($this->rules() as $rule) {
            
            $isRuleValid = $rule->isValid($validationSubject);
            
            if( ! $isRuleValid ) {
                $isValid = false;
                $errorMessages[] = $rule->getErrorMessage();
                
                if($this->stopInFirstError) {
                    break;
                }
            }
        }

      return $this->createResult($isValid, $errorMessages);
    }

    /**
     * Returns the array of the rules.
     *
     * @return Rule[]
     */
    protected abstract function rules();

}
