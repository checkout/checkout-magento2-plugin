<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Account;

class SaveCard extends \Magento\Framework\App\Action\Action {

    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * SaveCard constructor.
     * @param Context $context
     * @param VaultHandlerService $vaultHandler
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\Redirect $redirect, 
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        parent::__construct($context);

        $this->redirect = $redirect;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Handles the controller method.
     *
     * Saves the credit card
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute() {
        // Get the card token from request
        $ckoCardToken = $this->getRequest()->getParam('cardToken');

        // Save the card
        try {
            $this->vaultHandler
                 ->setCardToken($ckoCardToken)
                 ->setCustomerId()
                 ->setCustomerEmail()
                 ->authorizeTransaction()
                 ->saveCard();

            $this->messageManager->addSuccessMessage(__('The payment card has been stored successfully.') );
        }
        catch(\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        
        return $this->redirect->create()->setPath('vault/cards/listAction');
    }
}