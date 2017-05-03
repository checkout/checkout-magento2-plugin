<?php

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
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;

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
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

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
            InvoiceService $invoiceService, 
            InvoiceRepositoryInterface $invoiceRepository
          ) 
        {
        parent::__construct($context, $gatewayConfig);

        $this->session              = $session;
        $this->gatewayConfig        = $gatewayConfig;
        $this->verifyPaymentService = $verifyPaymentService;
        $this->orderService         = $orderService;
        $this->storeCardService     = $storeCardService;
        $this->customerSession      = $customerSession;
        $this->vaultTokenFactory    = $vaultTokenFactory;
        $this->paymentTokenRepository   = $paymentTokenRepository;
       $this->invoiceService       = $invoiceService;
        $this->invoiceRepository    = $invoiceRepository;

   }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws LocalizedException
     */
    public function execute() {
        
        $resultRedirect = $this->getResultRedirect();
        $paymentToken   = $this->getRequest()->getParam('cko-payment-token');
        $quote          = $this->session->getQuote();
        
        try {

            // Process the response
            $response   = $this->verifyPaymentService->verifyPayment($paymentToken);
            $cardToken  = $response['card']['id'];
            
            if(isset($response['description']) && $response['description'] == 'Saving new card'){
                return $this->vaultCardAfterThreeDSecure( $response );
            }
            
            // Process the quote
            $this->validateQuote($quote);
            $this->assignGuestEmail($quote, $response['email']);

            if($response['status'] === 'Declined') {
                throw new LocalizedException(__('The transaction has been declined.'));
            }

            // Place the order
            $this->orderService->execute($quote, $cardToken, []);

            // Load some classes for extra order processing
            $manager = \Magento\Framework\App\ObjectManager::getInstance(); 
            $session = $manager->create('Magento\Checkout\Model\Session');
            $orderInterface = $manager->create('Magento\Sales\Api\Data\OrderInterface'); 

            // Load the order
            $order = $orderInterface->loadByIncrementId($session->getLastRealOrderId());

            // Update order status
            $order->setState('new');
            $order->setStatus($this->gatewayConfig->getNewOrderStatus());
            $order->save(); 

            // Delete comments history
            foreach ($order->getAllStatusHistory() as $orderComment) {
                $orderComment->delete();
            }
            
            // Add new comment
            $priceHelper = $manager->create('Magento\Framework\Pricing\Helper\Data'); 
            $price =  $order->getGrandTotal(); 
            $formattedPrice = $priceHelper->currency($price, true, false);
            $newComment = 'Authorized amout of ' . $formattedPrice . '. Transaction ID: ' . $response['id'];
            $order->addStatusToHistory($order->getStatus(), $newComment, false);

            // Save the order
            $order->save();

            // Generate invoice if needed
            if($this->gatewayConfig->getAutoGenerateInvoice() && $order->canInvoice()) {

                $amount = ChargeAmountAdapter::getStoreAmountOfCurrency($response['value'], $response['currency']);

                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                $invoice->setBaseGrandTotal($amount);
                $invoice->register();

                $this->invoiceRepository->save($invoice);

            }


            // Redirect to the success page
            return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
        
        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }

    /**
     * Performs 3-D Secure method when adding new card.
     *
     * @param array $response
     * @return void
     */
    public function vaultCardAfterThreeDSecure( array $response ){
        $cardToken = $response['card']['id'];
        
        $cardData = [];
        $cardData['expiryMonth']   = $response['card']['expiryMonth'];
        $cardData['expiryYear']    = $response['card']['expiryYear'];
        $cardData['last4']         = $response['card']['last4'];
        $cardData['paymentMethod'] = $response['card']['paymentMethod'];
        
        try{
            $paymentToken = $this->vaultTokenFactory->create($cardData, $this->customerSession->getCustomer()->getId());
            $paymentToken->setGatewayToken($cardToken);
            $paymentToken->setIsVisible(true);

            $this->paymentTokenRepository->save($paymentToken);
        } 
        catch (\Exception $ex) {
            $this->messageManager->addErrorMessage( $ex->getMessage() );
        }
        
        $this->messageManager->addSuccessMessage( __('Credit Card has been stored successfully') );
        
        return $this->_redirect( 'vault/cards/listaction/' );
    }
}
