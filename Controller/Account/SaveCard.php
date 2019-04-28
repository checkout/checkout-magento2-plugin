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

class SaveCard extends \Magento\Framework\App\Action\Action {

    /**
     * @var SaveCardService
     */
    protected $saveCardService;

    /**
     * SaveCard constructor.
     * @param Context $context
     * @param SaveCardService $saveCardService
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Model\Service\SaveCardService $saveCardService
    ) {
        parent::__construct($context);
        $this->saveCardService = $saveCardService;
    }

    /**
     * Handles the controller method.
     *
     * Saves the credit card
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute() {
        // Get the card token from request
        $ckoCardToken = $this->getRequest()->getParams('cardToken');

        // Save the card
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
}