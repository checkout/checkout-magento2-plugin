<?php

namespace CheckoutCom\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;

class PlaceOrderObserver implements ObserverInterface {

    const REDIRECT_URL = 'redirectUrl';

    protected $request;
    protected $session;
    protected $responseFactory;

    public function __construct( RequestInterface $request, Session $session, ResponseFactory $responseFactory)
    {
        $this->request = $request;
        $this->session = $session;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Handles the observer for order placement.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) {
        
        // Get a potential 3D Secure redirect url from session
        $redirectUrl = $this->session->get3DSRedirect();

        // Get the card id charge flag
        $isCardIdCharge = $this->session->getCardIdChargeFlag() == 'cardIdCharge';

        // Handle a 3D Secure redirection if needed
        if ($this->isUrl($redirectUrl)) {

            // Unset the session variable
            $this->session->uns3DSRedirect();

            // Perform the redirection
            $this->responseFactory->create()->setRedirect($redirectUrl)->sendResponse();

            // Exit to force redirection in case of card id charge
            if ($isCardIdCharge) {
                // Unset the session variable
                $this->session->unsCardIdChargeFlag();
                exit();
            }
        }
    }

    public function isUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}
