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

use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Model\Methods\PaypalMethod;
use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

class SaveData
{
    protected ResultFactory $resultFactory;
    protected ManagerInterface $messageManager;
    protected RequestInterface $request;
    protected Session $checkoutSession;
    protected PaymentContextRequestService $paymentContextRequestService;
    protected RedirectFactory $redirectFactory;
    protected UrlInterface $urlInterface;
    protected CartRepositoryInterface $cartRepository;
    protected AddressInterfaceFactory $addressInterfaceFactory;
    protected PaypalMethod $paypalMethod;
    protected LoggerHelper $logger;

    public function __construct(
        LoggerHelper $loggerHelper,
        ResultFactory $resultFactory,
        ManagerInterface $messageManager,
        RequestInterface $request,
        Session $checkoutSession,
        PaymentContextRequestService $paymentContextRequestService,
        RedirectFactory $redirectFactory,
        UrlInterface $urlInterface,
        CartRepositoryInterface $cartRepository,
        AddressInterfaceFactory $addressInterfaceFactory,
        PaypalMethod $paypalMethod
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->paymentContextRequestService = $paymentContextRequestService;
        $this->redirectFactory = $redirectFactory;
        $this->urlInterface = $urlInterface;
        $this->cartRepository = $cartRepository;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->paypalMethod = $paypalMethod;
        $this->logger = $loggerHelper;
    }

    /**
     * Control about current request
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function shouldRedirectToCart(): bool
    {
        $quote = $this->checkoutSession->getQuote();
        $redirectToCart = false;

        $paymentContextId = $this->request->getParam(Review::PAYMENT_CONTEXT_ID_PARAMETER);

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

        // Check if context exists
        if (!$redirectToCart) {
            $contextDatas = $this->paymentContextRequestService->getPaymentContextById($paymentContextId, (int)$quote->getStoreId(), false);

            if (empty($contextDatas)) {
                $this->messageManager->addErrorMessage(__('We cannot find your payment informations, please try again'));
                $redirectToCart = true;
            }
        }

        return $redirectToCart;
    }

    /**
     * Assign a shipping method to quote
     */
    protected function setShippingMethod(string $methodCode, CartInterface $quote): bool
    {
        try {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
            $cartExtension = $quote->getExtensionAttributes();
            if ($cartExtension && $cartExtension->getShippingAssignments()) {
                $cartExtension->getShippingAssignments()[0]
                    ->getShipping()
                    ->setMethod($methodCode);
            }
            $quote->collectTotals();

            $this->cartRepository->save($quote);

            return true;
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));

            return false;
        }
    }
}
