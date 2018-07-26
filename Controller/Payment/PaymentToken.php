<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Gateway\Config\Config;

class PaymentToken extends Action {
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var Config
     */
    protected $config;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PaymentTokenService $paymentTokenService,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentTokenService = $paymentTokenService;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {
        return $this->resultJsonFactory->create()->setData(
            $this->paymentTokenService->getToken()
        );
    }
}
