<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Account;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class SaveCard
 */
class SaveCard extends \Magento\Framework\App\Action\Action
{

    /**
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var UrlInterface
     */
    public $urlInterface;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect
     */
    public $redirectFactory;

    /**
     * SaveCard constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \Magento\Framework\Controller\ResultFactory $redirectFactory

    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->urlInterface = $urlInterface;
        $this->vaultHandler = $vaultHandler;
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        // Prepare the parameters
        $success = false;
        $url = $this->urlInterface->getUrl('vault/cards/listaction');
        $message = __('The card could not be saved.');
        $ckoCardToken = $this->getRequest()->getParam('cardToken');

        // Process the request
        if ($this->getRequest()->isAjax() && !empty($ckoCardToken)) {
            // Save the card
            $success = $this->vaultHandler->setCardToken($ckoCardToken)
            ->setCustomerId()
            ->setCustomerEmail()
            ->authorizeTransaction();

            if (isset($success->response->_links['redirect']['href'])) {
                $redirect = $this->redirectFactory->create(ResultFactory::TYPE_REDIRECT);
                $threeDsUrl = $success->response->_links['redirect']['href'];
                $redirect->setUrl($threeDsUrl);
                return $redirect;
            }
            $success->saveCard();

            $this->messageManager->addSuccessMessage(__('The payment card has been stored successfully.'));
        }

        // Build the AJAX response
        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'message' => $message,
            'url' => $url
        ]);
    }
}
