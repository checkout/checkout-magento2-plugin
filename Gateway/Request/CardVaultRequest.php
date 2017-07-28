<?php

namespace CheckoutCom\Magento2\Gateway\Request;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Gateway\Helper\SubjectReader;
use Magento\Checkout\Model\Session;

class CardVaultRequest extends AbstractRequest {

    /**
     * @var Session
     */
    protected $session;

    public function __construct(Config $config, SubjectReader $subjectReader, Session $session) {
        parent::__construct($config, $subjectReader);
        $this->session = $session;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {
        $paymentDO  = $this->subjectReader->readPayment($buildSubject);
        $payment    = $paymentDO->getPayment();

        // Set a session flag for the card id chage
        $this->session->setCardIdChargeFlag('cardIdCharge');

        // Return the parameters
        return [
            'cardId' => $payment->getExtensionAttributes()->getVaultPaymentToken()->getGatewayToken(),
            'udf1' => 'cardIdCharge'
        ];
    }

}
