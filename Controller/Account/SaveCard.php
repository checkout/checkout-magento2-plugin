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
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->storeManager = $storeManager;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Prepare the parameters
        $success = false;
        $url = $this->storeManager->getUrl('vault/cards/listaction');
        $message = __('The card could not be saved.');
        $ckoCardToken = $this->getRequest()->getParam('cardToken');

        // Process the request
        if ($this->getRequest()->isAjax() && !empty($ckoCardToken)) {
            // Save the card
            try {
                $success = $this->vaultHandler
                 ->setCardToken($ckoCardToken)
                 ->setCustomerId()
                 ->setCustomerEmail()
                 ->authorizeTransaction()
                 ->saveCard();

                $this->messageManager->addSuccessMessage(__('The payment card has been stored successfully.'));
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }
        }

        // Build the AJAX response
        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'message' => $message,
            'url' => $url
        ]);
    }
}