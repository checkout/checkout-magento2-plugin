<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Response;

use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Checkout\Model\Session;
use CheckoutCom\Magento2\Model\Service\VerifyPaymentService;
use CheckoutCom\Magento2\Model\Factory\VaultTokenFactory;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Helper\Watchdog;

class TransactionHandler implements HandlerInterface {

    const REDIRECT_URL = 'redirectUrl';

    /**
     * @var Session 
     */
    protected $customerSession;

    /**
     * @var VerifyPaymentService
     */
    protected $verifyPaymentService;

    /**
     * @var VaultTokenFactory 
     */
    protected $vaultTokenFactory;
    
    /**
     * @var PaymentTokenRepository 
     */
    protected $paymentTokenRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    protected $messageManager;

    public function __construct(
        Session $session,
        VaultTokenFactory $vaultTokenFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenManagementInterface $paymentTokenManagement,
        CustomerSession $customerSession, 
        ManagerInterface $messageManager,
        VerifyPaymentService $verifyPaymentService,
        Watchdog $watchdog
    ) {
        $this->session = $session;
        $this->vaultTokenFactory    = $vaultTokenFactory;
        $this->paymentTokenRepository   = $paymentTokenRepository;
        $this->paymentTokenManagement   = $paymentTokenManagement;
        $this->verifyPaymentService = $verifyPaymentService;
        $this->messageManager    = $messageManager;
        $this->customerSession      = $customerSession;
        $this->watchdog = $watchdog;
    }
       
    /**
     * List of additional details
     * @var array
     */
    protected static $additionalInformationMapping = [
        'status',
        'responseMessage',
        'responseAdvancedInfo',
        'responseCode',
        'authCode',
    ];

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response) {
        
        /** @var $payment Payment */
        $paymentDO  = SubjectReader::readPayment($handlingSubject);
        $payment    = $paymentDO->getPayment();

        if( ! $payment instanceof Payment) {
            return;
        }

        // Debug info
        $this->watchdog->bark($response);
        
        // Set transaction info
        $this->setTransactionId($payment, $response['id']);
        $payment->setIsTransactionClosed( $this->shouldCloseTransaction() );
        $payment->setShouldCloseParentTransaction( $this->shouldCloseParentTransaction($payment) );

        if(array_key_exists('originalId', $response)) {
            $payment->setParentTransactionId($response['originalId']);
        }

        foreach(self::$additionalInformationMapping as $item) {
            if (array_key_exists($item, $response)) {
                $payment->setAdditionalInformation($item, $response[$item]);
            }
        }

        $responseCode = (int) $response['responseCode'];

        if($responseCode === 10100) {
            $payment->setIsFraudDetected(true);
        }
        elseif($responseCode >= 20000 AND $responseCode <= 40000) {
            $payment->setIsTransactionClosed(true);
        }

        // Prepare 3D Secure redirection with session variable for post auth order
        if (array_key_exists(self::REDIRECT_URL, $response)) {
            
            // Get the 3DS redirection URL
            $redirectUrl = $response[self::REDIRECT_URL];
            
            // Set 3DS redirection in session for the PlaceOrder controller
            $this->session->set3DSRedirect($redirectUrl);

            // Put the response in session for the PlaceOrder controller
            $this->session->setGatewayResponseId($response['id']);
        }

        // Set a flag for card id charge
        if (isset($response['udf1']) && $response['udf1'] == 'cardIdCharge') {
            $this->session->setCardIdChargeFlag('cardIdCharge');
        }

        // Save card if needed
        if (isset($response['udf2']) && $response['udf2'] == 'storeInVaultOnSuccess') {
            $this->vaultCard( $response );
        }
    }

    /**
     * Adds a new card.
     *
     * @param array $response
     * @return void
     */
    public function vaultCard( array $response ){
        $cardToken = $response['card']['id'];
        
        $cardData = [];
        $cardData['expiryMonth']   = $response['card']['expiryMonth'];
        $cardData['expiryYear']    = $response['card']['expiryYear'];
        $cardData['last4']         = $response['card']['last4'];
        $cardData['paymentMethod'] = $response['card']['paymentMethod'];

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
        catch (\Exception $ex) {
            $this->messageManager->addErrorMessage( $ex->getMessage() );
        }
    }

    /**
     * Sets the transaction Ids for the payment.
     *
     * @param Payment $payment
     * @param string $transactionId
     * @return void
     */
    protected function setTransactionId(Payment $payment, $transactionId) {
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->setCcTransId($transactionId);
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction() {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $payment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $payment) {
        return false;
    }
}
