<?php

namespace CheckoutCom\Magento2\Gateway\Response;

use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Checkout\Model\Session;

class TransactionHandler implements HandlerInterface {

    const REDIRECT_URL = 'redirectUrl';

    /**
     * @var Session
     */
    protected $session;

    public function __construct(Session $session) {
        $this->session = $session;
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
     
        $this->setTransactionId($payment, $response['id']);
        //$payment->setTransactionAdditionalInfo('Status', $response['status']);
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

        // Prepare 3D Secure redirection with session variable
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
