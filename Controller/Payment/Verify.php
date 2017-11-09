<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\OrderService;
use CheckoutCom\Magento2\Model\Service\VerifyPaymentService;
use CheckoutCom\Magento2\Model\Service\StoreCardService;
use CheckoutCom\Magento2\Model\Factory\VaultTokenFactory;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Watchdog;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Verify extends AbstractAction {

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var VerifyPaymentService
     */
    protected $verifyPaymentService;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var StoreCardService 
     */
    protected $storeCardService;
    
    /**
     * @var Session 
     */
    protected $customerSession;
    
    /**
     * @var VaultTokenFactory 
     */
    protected $vaultTokenFactory;
    
    /**
     * @var PaymentTokenRepository 
     */
    protected $paymentTokenRepository;

    /**
     * @var ResultRedirect 
     */
    protected $redirect;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * Verify constructor.
     * @param Context $context
     * @param Session $session
     * @param GatewayConfig $gatewayConfig
     * @param VerifyPaymentService $verifyPaymentService
     * @param OrderService $orderService
     * @param StoreCardService $storeCardService
     * @param CustomerSession $customerSession
     * @param VaultTokenFactory $vaultTokenFactory
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param OrderSender $orderSender
     * @param Watchdog $watchdog
     */
    public function __construct(
            Context $context, 
            Session $session, 
            GatewayConfig $gatewayConfig, 
            VerifyPaymentService $verifyPaymentService, 
            OrderService $orderService, 
            StoreCardService $storeCardService, 
            CustomerSession $customerSession, 
            VaultTokenFactory $vaultTokenFactory, 
            PaymentTokenRepositoryInterface $paymentTokenRepository,
            PaymentTokenManagementInterface $paymentTokenManagement,
            QuoteManagement $quoteManagement,
            OrderSender $orderSender,
            Watchdog $watchdog
          ) 
        {
            parent::__construct($context, $gatewayConfig);

            $this->quoteManagement          = $quoteManagement;
            $this->session                  = $session;
            $this->gatewayConfig            = $gatewayConfig;
            $this->verifyPaymentService     = $verifyPaymentService;
            $this->orderService             = $orderService;
            $this->storeCardService         = $storeCardService;
            $this->customerSession          = $customerSession;
            $this->vaultTokenFactory        = $vaultTokenFactory;
            $this->paymentTokenRepository   = $paymentTokenRepository;
            $this->paymentTokenManagement   = $paymentTokenManagement;
            $this->orderSender              = $orderSender;
            $this->watchdog                 = $watchdog;
            $this->redirect                 = $this->getResultRedirect();
        }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws LocalizedException
     */
    public function execute() {
        // Get the payment token from response
        $paymentToken = $this->extractPaymentToken();

        // Finalize the process
        return $this->finalizeProcess($paymentToken);
    }

    public function finalizeProcess($paymentToken) {
        // Process the gateway response
        $response = $this->verifyPaymentService->verifyPayment($paymentToken);

        // Debug info
        $this->watchdog->bark($response);

        // If it's an alternative payment
        if (isset($response['chargeMode']) && (int) $response['chargeMode'] == 3) {
            if (isset($response['responseCode']) && (int) $response['responseCode'] == 10000 || (int) $response['responseCode'] == 10100) {

                // Place a local payment order
                $this->placeLocalPaymentOrder();
            }
            else {
                $this->messageManager->addErrorMessage($response['responseMessage']);                
            }
        }

        // If it's a vault card id charge response
        else if (isset($response['udf1']) && $response['udf1'] == 'cardIdCharge') {
            if (isset($response['responseCode']) && (int) $response['responseCode'] == 10000) {
                $this->messageManager->addSuccessMessage( __('Order successfully processed.') );
            }
            else {
                $this->messageManager->addErrorMessage($response['responseMessage']);                
            }

            return $this->redirect->setPath('checkout/onepage/success', ['_secure' => true]);
        }

        // Else proceed normally for 3D Secure
        else {
            if (isset($response['udf2']) && $response['udf2'] == 'storeInVaultOnSuccess') {
                $this->vaultCardAfterThreeDSecure( $response );
            }

            // Check for declined transactions
            if (isset($response['status']) && $response['status'] === 'Declined') {
                throw new LocalizedException(__('The transaction has been declined.'));
            }

            // Update the order information
            try {
                // Redirect to the success page
                return $this->redirect->setPath('checkout/onepage/success', ['_secure' => true]);

            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            }
        }

        // Redirect to cart by default if the order validation fails
        return $this->redirect->setPath('checkout/onepage/success', ['_secure' => true]);
    }

    public function placeLocalPaymentOrder() {

        // Get the quote from session
        $quote = $this->session->getQuote();

        // Prepare the quote in session (required for success page redirection)
        $this->session
        ->setLastQuoteId($quote->getId())
        ->setLastSuccessQuoteId($quote->getId())
        ->clearHelperData();

        // Set payment
        $payment = $quote->getPayment();
        $payment->setMethod('checkmo');
        $quote->save();

        // Save the quote
        $quote->collectTotals()->save();

        try {

            // Create order from quote
            $order = $this->quoteManagement->submit($quote);
            
            // Prepare the order in session (required for success page redirection)
            if ($order) {
                $this->session->setLastOrderId($order->getId())
                                       ->setLastRealOrderId($order->getIncrementId())
                                       ->setLastOrderStatus($order->getStatus());
            
                // Update order status
                $order->setStatus($this->gatewayConfig->getOrderStatusCaptured());

                // Save the order
                $order->save();
            }
            
            // Redirect to the success page
            return $this->redirect->setPath('checkout/onepage/success', ['_secure' => true]);

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

    }

    public function extractPaymentToken() {

        // Get the gateway response from session if exists
        $gatewayResponseId = $this->session->getGatewayResponseId();

        // Destroy the session variable
        $this->session->unsGatewayResponseId();

        // Check if there is a payment token sent in url
        $ckoPaymentToken = $this->getRequest()->getParam('cko-payment-token');

        // Return the found payment token
        return $ckoPaymentToken ? $ckoPaymentToken : $gatewayResponseId;
    }

    /**
     * Performs 3-D Secure method when adding new card.
     *
     * @param array $response
     * @return void
     */
    public function vaultCardAfterThreeDSecure( array $response ){

        // Get the card token from response
        $cardToken = $response['card']['id'];
        
        // Prepare the card data
        $cardData = [];
        $cardData['expiryMonth']   = $response['card']['expiryMonth'];
        $cardData['expiryYear']    = $response['card']['expiryYear'];
        $cardData['last4']         = $response['card']['last4'];
        $cardData['paymentMethod'] = $response['card']['paymentMethod'];
        
        // Create the token object
        $paymentToken = $this->vaultTokenFactory->create($cardData, $this->customerSession->getCustomer()->getId());

        try {
            // Check if the payment token exists
            $foundPaymentToken = $this->paymentTokenManagement->getByPublicHash( $paymentToken->getPublicHash(), $paymentToken->getCustomerId());

            // If the token exists activate it, otherwise create it
            if ($foundPaymentToken) {
                $foundPaymentToken->setIsVisible(true);
                $foundPaymentToken->setIsActive(true);
                $this->paymentTokenRepository->save($foundPaymentToken);
            }
            else {
                $paymentToken->setGatewayToken($cardToken);
                $paymentToken->setIsVisible(true);
                $this->paymentTokenRepository->save($paymentToken);
            } 
            
            $this->messageManager->addSuccessMessage( __('The payment card has been stored successfully') );
        }    
        catch (\Exception $e) {
            $this->messageManager->addErrorMessage( $e->getMessage() );
        }
    }
}
