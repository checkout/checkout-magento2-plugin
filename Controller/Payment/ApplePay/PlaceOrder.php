<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Payment\ApplePay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\OrderService;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use CheckoutCom\Magento2\Model\Service\TokenChargeService;

class PlaceOrder extends Action {

    /**
     * @var TokenChargeService
     */
    protected $tokenChargeService;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param GatewayConfig $gatewayConfig
     * @param OrderInterface $orderInterface
     * @param OrderService $orderService
     * @param Order $orderManager
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        GatewayConfig $gatewayConfig,
        OrderService $orderService,
        OrderInterface $orderInterface,
        CustomerSession $customerSession,
        TokenChargeService $tokenChargeService
    ) {
        parent::__construct($context);

        $this->checkoutSession        = $checkoutSession;
        $this->customerSession        = $customerSession;
        $this->orderService           = $orderService;
        $this->orderInterface         = $orderInterface;
        $this->tokenChargeService     = $tokenChargeService;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {

        // Retrieve the request parameters
        $params = array(
            'cardToken' => $this->getRequest()->getParam('cko-card-token'),
            'email' => $this->getRequest()->getParam('cko-context-id'),
            'agreement' => array_keys($this->getRequest()->getPostValue('agreement', [])),
            'quote' => $this->checkoutSession->getQuote()
        );

        
        // If charge is successful, create order
        $this->createOrder($params);
    }

    public function createOrder($params) {

        // Check for guest email
        if ($params['quote']->getCustomerEmail() === null
            && $this->customerSession->isLoggedIn() === false
            && isset($this->customerSession->getData('checkoutSessionData')['customerEmail'])
            && $this->customerSession->getData('checkoutSessionData')['customerEmail'] === $params['email']) 
        {
            $params['quote']->setCustomerId(null)
            ->setCustomerEmail($params['email'])
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        // Perform quote and order validation
        try {
            // Create an order from the quote
            $this->validateQuote($params['quote']);
            
            /**
             *  Temporary workaround for a M2 code T&C checkbox issue not sending data.
             *  The last parameter should be $params['agreement']
             */
            $this->orderService->execute($params['quote'], $params['cardToken'], array(true));

            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
