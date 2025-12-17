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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Flow;

use CheckoutCom\Magento2\Model\Service\FlowPrepareService;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class Prepare implements ActionInterface, HttpGetActionInterface
{
    protected CheckoutSession $checkoutSession;
    protected FlowPrepareService $flowPrepareService;
    protected JsonFactory $resultJsonFactory;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        CheckoutSession $checkoutSession,
        FlowPrepareService $flowPrepareService,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->flowPrepareService = $flowPrepareService;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $quote = $this->checkoutSession->getQuote();

        if (empty($quote)) {
            return $result->setStatusHeader(400);
        }

        $data = $this->flowPrepareService->prepare($quote, array());

        if (empty($data['environment']) || empty($data['paymentSession']) || empty($data['publicKey'])) {
            return $result->setStatusHeader(400);
        }

        return $result->setData($data);
    }
}
