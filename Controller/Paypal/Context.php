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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Paypal;

use Checkout\Payments\Request\Source\AbstractRequestSource;
use Checkout\Payments\Request\Source\Contexts\PaymentContextsPayPalSource;
use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Context implements HttpPostActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected PaymentContextRequestService $paymentContextRequestService;
    protected RequestInterface $request;

    public function __construct(
        JsonFactory $resultJsonFactory,
        PaymentContextRequestService $paymentContextRequestService,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentContextRequestService = $paymentContextRequestService;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'content' => $this->paymentContextRequestService
                    ->setShippingFeesAsItem(false)
                    ->collectDiscountAmountOnItemUnitPrice(true)
                    ->setForceAuthorizeMode((bool)$this->request->getParam('forceAuthorizeMode'))
                    ->makePaymentContextRequests(
                        $this->getPaypalContext()
                    ),
            ]
        );

        return $resultJson;
    }

    private function getPaypalContext(): AbstractRequestSource
    {
        return new PaymentContextsPayPalSource();
    }
}
