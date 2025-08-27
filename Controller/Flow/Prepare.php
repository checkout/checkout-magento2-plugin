<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Flow;

use CheckoutCom\Magento2\Model\Service\FlowPrepareService;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;

class Prepare implements ActionInterface, HttpGetActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected ScopeConfigInterface $scopeConfig;
    protected FlowPrepareService $flowPrepareService;
    protected CustomerSession $customerSession;
    protected CheckoutSession $checkoutSession;

    public function __construct(
        FlowPrepareService $flowPrepareService,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession
    ) {
        $this->flowPrepareService = $flowPrepareService;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $quote = $this->checkoutSession->getQuote();
        $data = $this->flowPrepareService->prepare($quote, array());

        return $result->setData($data);
    }
}