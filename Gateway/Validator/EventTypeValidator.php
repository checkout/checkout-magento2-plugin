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
use CheckoutCom\Magento2\Model\Validator\Rule;

class EventTypeValidator extends ResponseValidator
{
    const FAILED_KEY = 'failed';
        
    /**
     * Returns the array of the rules.
     *
     * @return Rule[]
     */
    protected function rules() {
        return [
            new Rule('EventType Status', function(array $subject) {
                $response       = SubjectReader::readResponse($subject);
                $eventTypeParts = explode('.', $response['eventType']);
                
                return ! in_array(self::FAILED_KEY, $eventTypeParts, true);
            }, __('Checkout.com request failed.') ),
        ];
    }
    
}

