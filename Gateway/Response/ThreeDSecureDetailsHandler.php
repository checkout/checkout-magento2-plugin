<?php

namespace CheckoutCom\Magento2\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;

class ThreeDSecureDetailsHandler implements HandlerInterface {

    const CHARGE_MODE = 'chargeMode';

    const THREE_D_SECURED = 'three_d_secure';

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * ThreeDSecureDetailsHandler constructor.
     * @param ResponseFactory $responseFactory
     */
    public function __construct(ResponseFactory $responseFactory, Session $session) {
        $this->responseFactory = $responseFactory;
        $this->session = $session;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response) {

        if ( array_key_exists(self::CHARGE_MODE, $response) ) {
            $paymentDO  = SubjectReader::readPayment($handlingSubject);
            $payment    = $paymentDO->getPayment();
            $isEnabled  = $response[self::CHARGE_MODE] === 2 ? 'Yes' : 'No';

            $payment->setAdditionalInformation(self::THREE_D_SECURED, $isEnabled);
        }
    }

}
