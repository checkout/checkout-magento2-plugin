<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Payment;

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\FlowSessionCurrencyGuard;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class PlaceFlowOrder extends Action
{
    private const METHOD_PREFIX = "checkoutcom_";
    private const FLOW_ID = "checkoutcom_flow";

    private FlowGeneralSettings $flowGeneralConfig;
    private FlowSessionCurrencyGuard $currencyGuard;
    private JsonFactory $jsonFactory;
    protected JsonSerializer $json;
    private Logger $logger;
    private OrderHandlerService $orderHandler;
    private OrderRepositoryInterface $orderRepository;
    private QuoteHandlerService $quoteHandler;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        FlowGeneralSettings $flowGeneralConfig,
        FlowSessionCurrencyGuard $currencyGuard,
        JsonFactory $jsonFactory,
        Logger $logger,
        OrderHandlerService $orderHandler,
        OrderRepositoryInterface $orderRepository,
        QuoteHandlerService $quoteHandler,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->flowGeneralConfig = $flowGeneralConfig;
        $this->currencyGuard = $currencyGuard;
    }

    public function execute(): Json
    {
        return $this->processChecks();
    }

    private function processChecks(): Json
    {
        try {
            $json =  $this->jsonFactory->create();
            $websiteCode = $this->storeManager->getWebsite()->getCode();


            if (!$this->flowGeneralConfig->useFlow($websiteCode)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('Configuration Error'),
                ]);
            }

            $quote = $this->quoteHandler->getQuote();
            $data = $this->getRequest()->getParams();

            if (!isset($data['selectedMethod'])) {
                return $json->setData([
                    'success' => false,
                    'message' => __('Please enter valid payment details'),
                ]);
            }

            if (!$this->getRequest()->isAjax()) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The request is invalid'),
                ]);
            }

            if (empty($quote)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('No quote found'),
                ]);
            }

            // Checkout.com fixes the Flow session currency at creation. If the shopper switched
            // store currency (possibly from another tab) after the session was prepared, refuse
            // here so we never create an order bound to a session in the wrong currency. Placing
            // the order first and only catching this at submit time would leave an orphaned
            // pending-payment order behind.
            $sessionId = isset($data['session_id']) ? (string)$data['session_id'] : null;

            if ($this->currencyGuard->hasCurrencyChanged($sessionId, (string)$quote->getQuoteCurrencyCode())) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The currency has changed since payment was initialized. Please refresh the page and try again.'),
                ]);
            }

            $order = $this->orderHandler->setMethodId(self::FLOW_ID)->handleOrder($quote);

            if (!$this->orderHandler->isOrder($order)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The order could not be processed.'),
                ]);
            }

            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $this->attachPaymentInfos($order, $data['selectedMethod']);
            $this->orderRepository->save($order);

            return $json->setData([
                'success' => true,
                'reference' => $order->getIncrementId()
            ]);

        } catch (Exception $exception) {
            $this->logger->write($exception->getMessage());

            return $json->setData([
                'success' => false,
                'message' => __('An error has occurred, please select another payment method'),
            ]);
        }
    }

    private function attachPaymentInfos($order, $paymentName) {
        $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();

        $methodId = self::METHOD_PREFIX . $paymentName;

        $paymentInfo->setAdditionalInformation(
            'flow_method_id',
            $methodId
        );

        $order->setPayment($paymentInfo);
    }
}
