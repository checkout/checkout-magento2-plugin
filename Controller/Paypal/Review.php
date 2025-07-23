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

namespace CheckoutCom\Magento2\Controller\Paypal;

use CheckoutCom\Magento2\Model\Methods\PaypalMethod;
use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;

class Review implements HttpGetActionInterface
{
    public const PAYMENT_CONTEXT_ID_PARAMETER = 'contextId';
    public const SHIPPING_METHOD_PARAMETER = 'method_code';

    protected ResultFactory $resultFactory;
    protected ManagerInterface $messageManager;
    protected RequestInterface $request;
    protected Session $checkoutSession;
    protected PaymentContextRequestService $paymentContextRequestService;
    protected RedirectFactory $redirectFactory;
    protected UrlInterface $urlInterface;
    protected PaymentInterfaceFactory $paymentInterfaceFactory;
    protected PaypalMethod $paypalMethod;

    public function __construct(
        ResultFactory $resultFactory,
        ManagerInterface $messageManager,
        RequestInterface $request,
        Session $checkoutSession,
        PaymentContextRequestService $paymentContextRequestService,
        RedirectFactory $redirectFactory,
        UrlInterface $urlInterface,
        PaymentInterfaceFactory $paymentInterfaceFactory,
        PaypalMethod $paypalMethod
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->paymentContextRequestService = $paymentContextRequestService;
        $this->redirectFactory = $redirectFactory;
        $this->urlInterface = $urlInterface;
        $this->paymentInterfaceFactory = $paymentInterfaceFactory;
        $this->paypalMethod = $paypalMethod;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $quote = $this->checkoutSession->getQuote();
        $redirectToCart = false;
        $paymentContextId = $this->request->getParam(self::PAYMENT_CONTEXT_ID_PARAMETER);

        // Check quote
        if (!$quote || ($quote && !$quote->getId())) {
            $this->messageManager->addErrorMessage(__('Your Cart is empty'));
            $redirectToCart = true;
        }

        // Check if context is given
        if (!$redirectToCart && !$paymentContextId) {
            $this->messageManager->addErrorMessage(__('We cannot find your payment informations, please try again'));
            $redirectToCart = true;
        }

        if (!$redirectToCart) {
            /** @var PaymentInterface $paymentMethod */
            $paymentMethod = $this->paymentInterfaceFactory->create();
            $paymentMethod->setMethod($this->paypalMethod->getCode());

            $contextDatas = $this->paymentContextRequestService->getPaymentContextById($paymentContextId, (int)$quote->getStoreId(), true, $paymentMethod);

            if (empty($contextDatas)) {
                $this->messageManager->addErrorMessage(__('We cannot find your payment informations, please try again'));
                $redirectToCart = true;
            }
        }

        if ($redirectToCart) {
            return $this->redirectFactory->create()->setUrl($this->urlInterface->getUrl('checkout/cart'));
        }

        return $resultPage;
    }
}
