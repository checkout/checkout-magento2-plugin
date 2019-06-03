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
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * SaveCard constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->urlInterface = $urlInterface;
        $this->vaultHandler = $vaultHandler;
        $this->logger = $logger;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Prepare the parameters
        $success = false;
        $url = $this->urlInterface->getUrl('vault/cards/listaction');
        $message = __('The card could not be saved.');
        $ckoCardToken = $this->getRequest()->getParam('cardToken');

        // Process the request
        try {
            if ($this->getRequest()->isAjax() && !empty($ckoCardToken)) {
                // Save the card

                    $success = $this->vaultHandler
                    ->setCardToken($ckoCardToken)
                    ->setCustomerId()
                    ->setCustomerEmail()
                    ->authorizeTransaction()
                    ->saveCard();

                    $this->messageManager->addSuccessMessage(__('The payment card has been stored successfully.'));

            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            // Build the AJAX response
            return $this->jsonFactory->create()->setData([
                'success' => $success,
                'message' => $message,
                'url' => $url
            ]);
        }
    }
}