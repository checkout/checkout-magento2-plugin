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

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class PaymentToken extends AbstractAction {

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * PaymentToken constructor.
     * @param PaymentTokenService $paymentTokenService
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context, 
        PaymentTokenService $paymentTokenService,
        JsonFactory $resultJsonFactory,
        GatewayConfig $gatewayConfig
    ) 
    {
        parent::__construct($context, $gatewayConfig);
        $this->paymentTokenService  = $paymentTokenService;
        $this->resultJsonFactory    = $resultJsonFactory;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        $request = $this->getRequest();
        if ($request->isAjax()) {
            return $this->resultJsonFactory->create()->setData([
                'payment_token' => $this->paymentTokenService->getToken()
            ]);
        }
    }
}
