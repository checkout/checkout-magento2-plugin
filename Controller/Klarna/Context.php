<?php

declare(strict_types=1);
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

namespace CheckoutCom\Magento2\Controller\Klarna;

use Checkout\Common\AccountHolder;
use Checkout\Common\Address;
use Checkout\Payments\Request\Source\AbstractRequestSource;
use Checkout\Payments\Request\Source\Contexts\PaymentContextsKlarnaSource;
use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Context implements HttpPostActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected PaymentContextRequestService $paymentContextRequestService;
    protected RequestInterface $request;
    protected QuoteHandlerService $quoteHandlerService;

    public function __construct(
        JsonFactory $resultJsonFactory,
        PaymentContextRequestService $paymentContextRequestService,
        RequestInterface $request,
        QuoteHandlerService $quoteHandlerService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentContextRequestService = $paymentContextRequestService;
        $this->request = $request;
        $this->quoteHandlerService = $quoteHandlerService;
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'content' => $this->paymentContextRequestService->makePaymentContextRequests(
                    $this->getKlarnaContextSource(),
                    (bool)$this->request->getParam('forceAuthorizeMode'),
                    null,
                    null,
                    true
                ),
            ]
        );

        return $resultJson;
    }

    private function getKlarnaContextSource(): AbstractRequestSource
    {
        $klarnaRequestSource = new PaymentContextsKlarnaSource();
        $accountHolder = new AccountHolder();
        $billingAddress = new Address();
        $billingAddress->country =
            $this->quoteHandlerService->getBillingAddress()->getCountry() ?: $this->quoteHandlerService->getQuote()->getShippingAddress()->getCountry();
        $accountHolder->billing_address = $billingAddress;
        $klarnaRequestSource->account_holder = $accountHolder;

        return $klarnaRequestSource;
    }
}
