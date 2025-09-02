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

namespace CheckoutCom\Magento2\Observer\Backend;

use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Api\Data\PaymentResponse as PaymentResponseApi;
use CheckoutCom\Magento2\Model\Methods\PayByLinkMethod;
use CheckoutCom\Magento2\Model\Request\PostPaymentLinks;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class PayByLinkPaymentRequest implements ObserverInterface
{
    private Session $backendAuthSession;
    private ManagerInterface $messageManager;
    private ApiHandlerService $apiHandler;
    private OrderHandlerService $orderHandler;
    private Config $config;
    private Utilities $utilities;
    private Logger $logger;
    private PostPaymentLinks $postPaymentLinks;

    public function __construct(
        Session $backendAuthSession,
        ManagerInterface $messageManager,
        ApiHandlerService $apiHandler,
        OrderHandlerService $orderHandler,
        Config $config,
        Utilities $utilities,
        Logger $logger,
        PostPaymentLinks $postPaymentLinks
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->messageManager = $messageManager;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->postPaymentLinks = $postPaymentLinks;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     * @throws CheckoutArgumentException
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CheckoutApiException
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $methodId = $order->getPayment()->getMethodInstance()->getCode();
        $storeCode = $order->getStore()->getCode();

        // Process the payment
        if (!$this->needsPayByLinkProcessing($methodId, $order)) {
            return;
        }
        // Prepare the response container
        $response = null;

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        // Build request
        $request = $this->postPaymentLinks->get($order, $api);

        // Send the charge request
        try {
            $this->logger->display($request);
            $response = $api->getCheckoutApi()->getPaymentLinksClient()->createPaymentLink($request);
            $this->logger->display($response);
        } catch (CheckoutApiException $e) {
            $this->logger->write($e->getMessage());
        } finally {
            // Add the response link to the order payment data
            if (!is_array($response) || !$api->isValidResponse((array)$response)) {
                $this->messageManager->addErrorMessage(
                    __('The payment link request could not be processed. Please check the payment details.')
                );
                return;
            }

            $order->setStatus($this->config->getValue('order_status_waiting_payment', PayByLinkMethod::CODE, $storeCode, ScopeInterface::SCOPE_STORE))
                ->getPayment()->setAdditionalInformation(PayByLinkMethod::ADDITIONAL_INFORMATION_LINK_CODE, $response['_links']['redirect']['href']);
            if (isset($response['status'])) {
                if ($response['status'] === PaymentResponseApi::AUTHORIZED_STATUS_CODE) {
                    $this->messageManager->addSuccessMessage(
                        __('The payment link request was successfully processed.')
                    );
                } else {
                    $this->messageManager->addWarningMessage(__('Status: %1', $response['status']));
                }
            }
        }
    }

    /**
     * @param string $methodId
     * @param array $params
     *
     * @return bool
     */
    protected function needsPayByLinkProcessing(string $methodId, OrderInterface $order): bool
    {
        return $this->backendAuthSession->isLoggedIn() && $methodId === PayByLinkMethod::CODE && !$order->getPayment()->getAdditionalInformation(PayByLinkMethod::ADDITIONAL_INFORMATION_LINK_CODE);
    }
}
