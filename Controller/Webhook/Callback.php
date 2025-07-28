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

namespace CheckoutCom\Magento2\Controller\Webhook;

use CheckoutCom\Magento2\Api\WebhookInterface;
use CheckoutCom\Magento2\Exception\WebhookEventAlreadyExistsException;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\ShopperHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use CheckoutCom\Magento2\Model\Service\WebhookHandlerService;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Webapi\Exception as WebException;
use Magento\Framework\Webapi\Response;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Callback
 */
class Callback extends Action implements CsrfAwareActionInterface
{
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private ApiHandlerService $apiHandler;
    private OrderHandlerService $orderHandler;
    private ShopperHandlerService $shopperHandler;
    private WebhookHandlerService $webhookHandler;
    private VaultHandlerService $vaultHandler;
    private PaymentErrorHandlerService $paymentErrorHandler;
    private Config $config;
    private OrderRepositoryInterface $orderRepository;
    private Logger $logger;
    private Utilities $utilities;
    private JsonSerializer $json;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ApiHandlerService $apiHandler,
        OrderHandlerService $orderHandler,
        ShopperHandlerService $shopperHandler,
        WebhookHandlerService $webhookHandler,
        VaultHandlerService $vaultHandler,
        PaymentErrorHandlerService $paymentErrorHandler,
        Config $config,
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        Utilities $utilities,
        JsonSerializer $json
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->shopperHandler = $shopperHandler;
        $this->webhookHandler = $webhookHandler;
        $this->vaultHandler = $vaultHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->utilities = $utilities;
        $this->json = $json;
    }

    /**
     * Handles the controller method
     *
     * @return ResponseInterface|Json|ResultInterface|void
     */
    public function execute()
    {
        // Prepare the response handler
        $resultFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            // Set the payload data
            $payload = $this->getPayload();

            // Process the request
            if ($this->config->isValidAuth('psk')) {
                // Filter out verification requests
                if (isset($payload['type']) && $payload['type'] !== "card_verified") {
                    // Handle authentication_expired webhook
                    if ($payload['type'] === WebhookInterface::AUTHENTICATION_EXPIRED && isset($payload['data']['payment_id'])) {
                        $payload['data']['id'] = $payload['data']['payment_id'];
                        $payload['data']['action_id'] = WebhookInterface::AUTHENTICATION_EXPIRED;
                        $payload['type'] = WebhookInterface::PAYMENT_EXPIRED;
                    }

                    // Process the request
                    if (isset($payload['data']['id'])) {
                        // Get the store code
                        $storeCode = $this->storeManager->getStore()->getCode();

                        // Initialize the API handler
                        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

                        // Get the payment details
                        $response = $api->getPaymentDetails($payload['data']['id']);

                        if (isset($response['reference'])) {
                            // Find the order from increment id
                            $order = $this->orderHandler->getOrder([
                                'increment_id' => $response['reference'],
                            ]);

                            // Process the order
                            if ($this->orderHandler->isOrder($order)) {
                                if ($api->isValidResponse($response)) {
                                    // Get Source and set it to the order
                                    $order->getPayment()
                                        ->getMethodInstance()
                                        ->getInfoInstance()
                                        ->setAdditionalInformation(
                                            'cko_payment_information',
                                            array_intersect_key($response, array_flip(['source'])),
                                        );

                                    // Get 3ds information and set it to the order
                                    $order->getPayment()
                                        ->getMethodInstance()
                                        ->getInfoInstance()
                                        ->setAdditionalInformation(
                                            'cko_threeDs',
                                            array_intersect_key($response, array_flip(['threeDs'])),
                                        );

                                    // Save the order
                                    $this->orderRepository->save($order);

                                    // Handle the save card request
                                    if ($this->cardNeedsSaving($payload)) {
                                        $this->saveCard($response, $payload);
                                    }

                                    // Clean the webhooks table
                                    $clean = $this->scopeConfig->getValue(
                                        'settings/checkoutcom_configuration/webhooks_table_clean',
                                        ScopeInterface::SCOPE_WEBSITE
                                    );

                                    $cleanOn = $this->scopeConfig->getValue(
                                        'settings/checkoutcom_configuration/webhooks_clean_on',
                                        ScopeInterface::SCOPE_WEBSITE
                                    );

                                    // Save the webhook
                                    $this->webhookHandler->processSingleWebhook(
                                        $order,
                                        $payload
                                    );

                                    if ($clean && $cleanOn === 'webhook') {
                                        $this->webhookHandler->clean(true);
                                    }
                                } else {
                                    // Log the payment error
                                    $this->paymentErrorHandler->logError(
                                        $payload,
                                        $order
                                    );
                                }
                                // Set a valid response
                                $resultFactory->setHttpResponseCode(Response::HTTP_OK);

                                // Return the 200 success response
                                return $resultFactory->setData([
                                    'result' => __('Webhook and order successfully processed.'),
                                ]);
                            } else {
                                $resultFactory->setHttpResponseCode(WebException::HTTP_INTERNAL_ERROR);

                                return $resultFactory->setData([
                                    'error_message' => __(
                                        'The order creation failed. Please check the error logs.'
                                    ),
                                ]);
                            }
                        } else {
                            $resultFactory->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);

                            return $resultFactory->setData(['error_message' => __('The webhook response is invalid.')]);
                        }
                    } else {
                        $resultFactory->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);

                        return $resultFactory->setData(
                            ['error_message' => __('The webhook payment response is invalid.')]
                        );
                    }
                }
            } else {
                $resultFactory->setHttpResponseCode(WebException::HTTP_UNAUTHORIZED);

                return $resultFactory->setData([
                    'error_message' => __('Unauthorized request. No matching private shared key.'),
                ]);
            }
        } catch (WebhookEventAlreadyExistsException $e) {
            // Set a valid response to avoid gateway retry mechanism
            $resultFactory->setHttpResponseCode(Response::HTTP_OK);

            // Return the 200 success response
            return $resultFactory->setData([
                'result' => __('Webhook was already processed.'),
            ]);
        } catch (Exception $e) {
            // Throw 400 error for gateway retry mechanism
            $resultFactory->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);
            $this->logger->write($e->getMessage());

            return $resultFactory->setData([
                'error_message' => __(
                    'There was an error processing the webhook. Please check the error logs.'
                ),
            ]);
        }
    }

    /**
     * Get the request payload
     *
     * @return mixed
     */
    public function getPayload()
    {
        $this->logger->additional($this->getRequest()->getContent(), 'webhook');

        return $this->json->unserialize($this->getRequest()->getContent());
    }

    /**
     * Check if the card needs saving
     *
     * @param array $payload
     *
     * @return bool
     */
    protected function cardNeedsSaving(array $payload): bool
    {
        if (!isset($payload['data']['metadata'])) {
            return false;
        }
        $metadata = $payload['data']['metadata'];
        $id = $payload['data']['source']['id'] ?? '';
        $saveCard = 0;
        if (isset($metadata['saveCard']) || isset($metadata['save_card'])) {
            $saveCard = $metadata['saveCard'] ?? $metadata['save_card'];
        }
        $customerId = 0;
        if (isset($metadata['customerId']) || isset($metadata['customer_id'])) {
            $customerId = $metadata['customerId'] ?? $metadata['customer_id'];
        }

        return isset($saveCard, $customerId, $id) &&
               (int)$saveCard === 1 &&
               (int)$customerId > 0 &&
               !empty($id);
    }

    /**
     * Save a card
     *
     * @param array $response
     * @param array $payload
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function saveCard(array $response, array $payload): bool
    {
        // Get the customer
        $customerId = $payload['data']['metadata']['customerId'] ?? $payload['data']['metadata']['customer_id'];
        $customer = $this->shopperHandler->getCustomerData(['id' => $customerId]);

        // Save the card
        return $this->vaultHandler->setCardToken($payload['data']['source']['id'])->setCustomerId(
            $customer->getId()
        )->setCustomerEmail($customer->getEmail())->setResponse($response)->saveCard();
    }

    /**
     * createCsrfValidationException method
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * validateForCsrf method
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
