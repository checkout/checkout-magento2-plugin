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

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Methods\PaypalMethod;
use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;

/**
 * Class Review
 */
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
    protected CartRepositoryInterface $cartRepository;
    protected Logger $logger;

    public function __construct(
        ResultFactory $resultFactory,
        ManagerInterface $messageManager,
        RequestInterface $request,
        Session $checkoutSession,
        PaymentContextRequestService $paymentContextRequestService,
        RedirectFactory $redirectFactory,
        UrlInterface $urlInterface,
        PaymentInterfaceFactory $paymentInterfaceFactory,
        PaypalMethod $paypalMethod,
        CartRepositoryInterface $cartRepository,
        Logger $logger
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
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
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

        $this->autoSelectFirstShippingMethod($quote);

        return $resultPage;
    }

    /**
     * Pre-select the first available shipping method when express_auto_method is enabled
     * and no method has been chosen yet.
     *
     * @param CartInterface $quote
     *
     * @return void
     */
    private function autoSelectFirstShippingMethod(CartInterface $quote): void
    {
        try {
            if (!$this->paypalMethod->getConfigData('express_auto_method')) {
                return;
            }

            $shippingAddress = $quote->getShippingAddress();
            if (!$shippingAddress || $shippingAddress->getShippingMethod()) {
                return;
            }

            $shippingAddress->collectShippingRates();
            $rates = $shippingAddress->getGroupedAllShippingRates();
            foreach ($rates as $carrier) {
                foreach ($carrier as $carrierMethod) {
                    $shippingAddress->setShippingMethod($carrierMethod->getCode())->setCollectShippingRates(true);
                    $quote->collectTotals();
                    $this->cartRepository->save($quote);

                    return;
                }
            }
        } catch (Exception $e) {
            $this->logger->write('PayPal express auto shipping method selection failed: ' . $e->getMessage());
        }
    }
}
