<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Controller\Shopper;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Helper\Tools;

class SessionData extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Tools
     */
    protected $tools;

    public function __construct(Context $context, JsonFactory $resultJsonFactory, CustomerSession $customerSession, Tools $tools)
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->tools = $tools;
    }
 
    public function execute()
    {
    
    
        // Get the request data
        $sessionData = $this->tools->getInputData();

        // Save in session
        $result = $this->customerSession->setData('checkoutSessionData', $sessionData);

        // Return a JSON output with the result
        return $this->resultJsonFactory->create()->setData($result);
    }
}