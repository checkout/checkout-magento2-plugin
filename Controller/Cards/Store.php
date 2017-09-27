<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

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
          
        $ckoCardToken = $this->getCardToken();

        try {
            $this->storeCardService
                 ->setCardToken($ckoCardToken)
                 ->setCustomerId()
                 ->setCustomerEmail()
                 ->test()
                 ->setCardData()
                 ->save();

            $this->messageManager->addSuccessMessage( __('The payment card has been stored successfully.') );
        }
        catch(\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        
        return $this->_redirect('vault/cards/listAction');
    }    

    public function getCardToken() {

        $params = array_keys($this->getRequest()->getParams());

        if (isset($params[0])) {
            $params = json_decode($params[0]);

            if (is_object($params)) {
                return $params->ckoCardToken;
            }
        }

        return false;
    }

}
