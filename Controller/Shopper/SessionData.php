<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Shopper;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session as CustomerSession;

class SessionData extends Action
{
    protected $customerSession;
 
    public function __construct(Context $context, CustomerSession $customerSession)
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
    }
 
    public function execute()
    {
        // Get the request data
        $sessionData = $this->getInputData();

        // Save in session
        $this->customerSession->setData('checkoutSessionData', $sessionData);

        // End the script
        exit();
    }

    public function getInputData() {

        // Get all parameters from request
        $params = $this->getRequest()->getParams();

        // Sanitize the array
        $params = array_map(function($val) {
            return filter_var($val, FILTER_SANITIZE_STRING);
        }, $params);

        return $params;
    }
}