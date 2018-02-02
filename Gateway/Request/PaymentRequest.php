<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Request;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Gateway\Helper\SubjectReader;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class PaymentRequest extends AbstractRequest {

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * PaymentRequest constructor.
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @param RemoteAddress $remoteAddress
     * @param CustomerSession $customerSession
     */
    public function __construct(Config $config, SubjectReader $subjectReader, RemoteAddress $remoteAddress, CustomerSession $customerSession) {
        parent::__construct($config, $subjectReader);

        $this->remoteAddress    = $remoteAddress;
        $this->customerSession  = $customerSession;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {
        $paymentDO      = $this->subjectReader->readPayment($buildSubject);
        $order          = $paymentDO->getOrder();

        $data = [
            'autoCapTime'   => $this->config->getAutoCaptureTimeInHours(),
            'autoCapture'   => $this->config->isAutoCapture() ? 'Y' : 'N',
            'email'         => $order->getBillingAddress()->getEmail(),
            'currency'      => ChargeAmountAdapter::getPaymentFinalCurrencyCode($order->getCurrencyCode()),
            'customerIp'    => $this->remoteAddress->getRemoteAddress(),
        ];

        if($this->customerSession->isLoggedIn()) {
            $data['customerName'] = substr($this->customerSession->getCustomer()->getName(), 0, 100);
        }

        return $data;
    }

}
