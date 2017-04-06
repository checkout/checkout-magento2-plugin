<?php

namespace CheckoutCom\Magento2\Controller\Cards;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use CheckoutCom\Magento2\Model\Service\StoreCardService;

class Store extends Action {

    /**
     * @var StoreCardService
     */
    protected $storeCardService;

    /**
     * Store constructor.
     * @param Context $context
     * @param StoreCardService $storeCardService
     */
    public function __construct(Context $context, StoreCardService $storeCardService) {
        parent::__construct($context);

        $this->storeCardService = $storeCardService;
    }

    /**
     * Handles the controller method.
     *
     * Saves the credit card
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute() {
        $cardToken      = $this->getRequest()->getParam('cko-card-token');
        $cardData       = $this->getRequest()->getParam('cko-card');
        $customerEmail  = $this->getRequest()->getParam('customer_email');
        $customerId     = $this->getRequest()->getParam('customer_id');
        $customerName   = $this->getRequest()->getParam('customer_name');
       
        try {
            $this->storeCardService
                ->setCardTokenAndData($cardToken, $cardData)
                ->setCustomerEmail($customerEmail)
                ->setCustomerId($customerId)
                ->setCustomerName($customerName)
                ->save();

            $this->messageManager->addSuccessMessage( __('Credit Card has been stored successfully') );
        }
        catch(\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        
        return $this->_redirect('vault/cards/listAction');
    }    

}
