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

namespace CheckoutCom\Magento2\Controller\Payment;

use CheckoutCom\Magento2\Helper\Logger;
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

    private StoreManagerInterface $storeManager;
    private JsonFactory $jsonFactory;
    private QuoteHandlerService $quoteHandler;
    private OrderHandlerService $orderHandler;
    private Logger $logger;
    private OrderRepositoryInterface $orderRepository;
    protected JsonSerializer $json;
    private FlowGeneralSettings $flowGeneralConfig;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        FlowGeneralSettings $flowGeneralConfig
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->flowGeneralConfig = $flowGeneralConfig;
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
            ]);

        } catch (Exception $e) {
            $this->logger->write($e->getMessage());

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
