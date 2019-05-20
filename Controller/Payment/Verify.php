<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class Verify extends \Magento\Framework\App\Action\Action {

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler
    )
    {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // $this->apiHandler->getPaymentDetails($paymentId)
        $postData = $this->getRequest()->getPostValue();

        var_dump($postData);
        exit();
    }

    protected function isValidRequest () {
        $authorization = $this->getRequest()->getHeader('Authorization');
        $secretKey = $this->config->getValue('secret_key');
        return $authorization == $secretKey;
    }
}
