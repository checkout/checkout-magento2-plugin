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
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;

class Context implements HttpPostActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected PaymentContextRequestService $paymentContextRequestService;
    protected RequestInterface $request;
    protected QuoteHandlerService $quoteHandlerService;
    protected Logger $logger;
    protected SerializerInterface $serializer;

    public function __construct(
        JsonFactory $resultJsonFactory,
        PaymentContextRequestService $paymentContextRequestService,
        RequestInterface $request,
        QuoteHandlerService $quoteHandlerService,
        SerializerInterface $serializer,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentContextRequestService = $paymentContextRequestService;
        $this->request = $request;
        $this->quoteHandlerService = $quoteHandlerService;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $resultJson->setData(
                [
                    'content' => $this->paymentContextRequestService
                        ->setShippingFeesAsItem(true)
                        ->collectDiscountAmountOnItemUnitPrice(false)
                        ->setForceAuthorizeMode((bool)$this->request->getParam('forceAuthorizeMode'))
                        ->makePaymentContextRequests(
                            $this->getKlarnaContextSource()
                        ),
                ]
            );
        } catch (Exception $e) {
            $this->logger->write(sprintf('Error happen while requesting klarna context: %s', $e->getMessage()));
            $resultJson->setData(
                [
                    'content' => [
                        'error' => __('Error happen while requesting context, please see logs'),
                    ],
                ]
            );
        }

        return $resultJson;
    }

    private function getKlarnaContextSource(): AbstractRequestSource
    {
        $klarnaRequestSource = new PaymentContextsKlarnaSource();
        $accountHolder = new AccountHolder();
        $billingAddress = new Address();
        $billingAddress->country = $this->getCountryId();
        $accountHolder->billing_address = $billingAddress;
        $klarnaRequestSource->account_holder = $accountHolder;

        return $klarnaRequestSource;
    }

    /**
     *  If a country id is given on request parameters give it first, if no use the quote one
     */
    private function getCountryId(): string
    {
        return (string)$this->serializer->unserialize((string)$this->request->getContent())['country'] ?: $this->quoteHandlerService->getBillingAddress()->getCountry();
    }
}
