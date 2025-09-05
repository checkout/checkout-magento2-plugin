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

namespace CheckoutCom\Magento2\Controller\Payment;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PlaceOrder
 */
class PlaceFlowOrder extends Action
{
    private StoreManagerInterface $storeManager;
    private JsonFactory $jsonFactory;
    private ScopeConfigInterface $scopeConfig;
    private QuoteHandlerService $quoteHandler;
    private OrderHandlerService $orderHandler;
    private OrderStatusHandlerService $orderStatusHandler;
    private MethodHandlerService $methodHandler;
    private ApiHandlerService $apiHandler;
    private PaymentErrorHandlerService $paymentErrorHandler;
    private Utilities $utilities;
    private Logger $logger;
    private Session $session;
    private OrderRepositoryInterface $orderRepository;
    protected JsonSerializer $json;
    private Config $config;
    private FlowGeneralSettings $flowGeneralConfig;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory,
        ScopeConfigInterface $scopeConfig,
        QuoteHandlerService $quoteHandler,
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        MethodHandlerService $methodHandler,
        ApiHandlerService $apiHandler,
        PaymentErrorHandlerService $paymentErrorHandler,
        Utilities $utilities,
        Logger $logger,
        Session $session,
        OrderRepositoryInterface $orderRepository,
        JsonSerializer $json,
        Config $config,
        FlowGeneralSettings $flowGeneralConfig
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->session = $session;
        $this->orderRepository = $orderRepository;
        $this->json = $json;
        $this->config = $config;
        $this->flowGeneralConfig = $flowGeneralConfig;
    }

    /**
     * Main controller function
     *
     * @return Json
     */
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

            if (!isset($data['methodId'])) {
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
            
            $order = $this->orderHandler->setMethodId($data['methodId'])->handleOrder($quote);

            if (!$this->orderHandler->isOrder($order)) {
                return $json->setData([
                    'success' => false,
                    'message' => __('The order could not be processed.'),
                ]);
            }

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
}
