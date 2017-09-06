<?php

namespace CheckoutCom\Magento2\Controller\Cards;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use CheckoutCom\Magento2\Model\Service\StoreCardService;


use Magento\Vault\Api\PaymentTokenManagementInterface;
    use Magento\Customer\Model\Session;


class Test extends Action {

    /**
     * @var StoreCardService
     */
    protected $storeCardService;
    protected $p;
    protected $s;

    /**
     * Store constructor.
     * @param Context $context
     * @param StoreCardService $storeCardService
     */
    public function __construct(Context $context, StoreCardService $storeCardService, PaymentTokenManagementInterface $p, Session $s) {
        parent::__construct($context);

        $this->storeCardService = $storeCardService;
        $this->p = $p;
        $this->s = $s;
    }

    /**
     * Handles the controller method.
     *
     * Saves the credit card
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute() {
          


// Get the customer id (currently logged in user)
$customerId = $this->s->getCustomer()->getId();

// Card list
$cardList = $this->p->getListByCustomerId($customerId);


foreach ($cardList as $card) {

echo "<pre>";
var_dump($card->getData());
echo "</pre>";


}

exit();



        $ckoCardToken = $this->getCardToken();

        try {
            $this->storeCardService
                 ->setCardToken($ckoCardToken)
                 ->setCustomerId()
                 ->setCustomerEmail()
                 ->test()
                 ->setCardData()
                 ->save();

            $this->messageManager->addSuccessMessage( __('Credit Card has been stored successfully') );
        }
        catch(\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        
        return $this->_redirect('vault/cards/listAction');
    }    

    public function getCardToken() {

        $params = array_keys($this->getRequest()->getParams());
        $params = json_decode($params[0]);

        return $params->ckoCardToken;
    }

}
