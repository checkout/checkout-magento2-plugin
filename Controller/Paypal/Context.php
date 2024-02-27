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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Paypal;

use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Context extends Action
{
    protected JsonFactory $jsonFactory;
    protected PaymentContextRequestService $paymentContextRequestService;

    public function __construct(
        JsonFactory $jsonFactory,
        PaymentContextRequestService $paymentContextRequestService
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->paymentContextRequestService = $paymentContextRequestService;
    }

    public function execute(): ResponseInterface | ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'content' => $this->paymentContextRequestService->makePaymentContextRequests('paypal')
            ]
        );

        return $resultJson;
    }
}
