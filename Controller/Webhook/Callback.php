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

use Checkout\Models\Payments\Payment;
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
use Magento\Framework\Webapi\Exception as WebException;
use Magento\Framework\Webapi\Rest\Response as WebResponse;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Callback
 */
class Callback extends Action implements CsrfAwareActionInterface
{
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * $shopperHandler field
     *
     * @var ShopperHandlerService $shopperHandler
     */
    private $shopperHandler;
    /**
     * $webhookHandler field
     *
     * @var WebhookHandlerService $webhookHandler
     */
    private $webhookHandler;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    private $vaultHandler;
    /**
     * $paymentErrorHandler field
     *
     * @var PaymentErrorHandlerService $paymentErrorHandler
     */
    private $paymentErrorHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;

    /**
     * Callback constructor
     *
     * @param Context                    $context
     * @param StoreManagerInterface      $storeManager
     * @param ScopeConfigInterface       $scopeConfig
     * @param ApiHandlerService          $apiHandler
     * @param OrderHandlerService        $orderHandler
     * @param ShopperHandlerService      $shopperHandler
     * @param WebhookHandlerService      $webhookHandler
     * @param VaultHandlerService        $vaultHandler
     * @param PaymentErrorHandlerService $paymentErrorHandler
     * @param Config                     $config
     * @param Logger                     $logger
     */
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
        Logger $logger
    ) {
        parent::__construct($context);

        $this->storeManager        = $storeManager;
        $this->scopeConfig         = $scopeConfig;
        $this->apiHandler          = $apiHandler;
        $this->orderHandler        = $orderHandler;
        $this->shopperHandler      = $shopperHandler;
        $this->webhookHandler      = $webhookHandler;
        $this->vaultHandler        = $vaultHandler;
        $this->paymentErrorHandler = $paymentErrorHandler;
        $this->config              = $config;
        $this->logger              = $logger;
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
            /** @var mixed $payload */
            $payload = $this->getPayload();

            // Process the request
            if ($this->config->isValidAuth('psk')) {
                // Filter out verification requests
                if ($payload->type !== "card_verified") {
                    // Process the request
                    if (isset($payload->data->id)) {
                        // Get the store code
                        $storeCode = $this->storeManager->getStore()->getCode();

                        // Initialize the API handler
                        $api = $this->apiHandler->init($storeCode);

                        // Get the payment details
                        $response = $api->getPaymentDetails($payload->data->id);

                        if (isset($response->reference)) {
                            // Find the order from increment id
                            $order = $this->orderHandler->getOrder([
                                'increment_id' => $response->reference,
                            ]);

                            // Process the order
                            if ($this->orderHandler->isOrder($order)) {
                                if ($api->isValidResponse($response)) {
                                    // Handle the save card request
                                    if ($this->cardNeedsSaving($payload)) {
                                        $this->saveCard($response, $payload);
                                    }

                                    // Clean the webhooks table
                                    $clean = $this->scopeConfig->getValue(
                                        'settings/checkoutcom_configuration/webhooks_table_clean',
                                        ScopeInterface::SCOPE_STORE
                                    );

                                    $cleanOn = $this->scopeConfig->getValue(
                                        'settings/checkoutcom_configuration/webhooks_clean_on',
                                        ScopeInterface::SCOPE_STORE
                                    );

                                    // Save the webhook
                                    $this->webhookHandler->processSingleWebhook(
                                        $order,
                                        $payload
                                    );

                                    if ($clean && $cleanOn === 'webhook') {
                                        $this->webhookHandler->clean();
                                    }
                                } else {
                                    // Log the payment error
                                    $this->paymentErrorHandler->logError(
                                        $payload,
                                        $order
                                    );
                                }
                                // Set a valid response
                                $resultFactory->setHttpResponseCode(WebResponse::HTTP_OK);

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

        return json_decode($this->getRequest()->getContent());
    }

    /**
     * Check if the card needs saving
     *
     * @param mixed $payload
     *
     * @return bool
     */
    protected function cardNeedsSaving($payload): bool
    {
        return isset(
            $payload->data->metadata->saveCard,
            $payload->data->metadata->customerId,
            $payload->data->source->id
        )  && (int)$payload->data->metadata->saveCard === 1
           && (int)$payload->data->metadata->customerId > 0
           && !empty($payload->data->source->id);
    }

    /**
     * Save a card
     *
     * @param Payment $response
     * @param mixed   $payload
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function saveCard(Payment $response, $payload): bool
    {
        // Get the customer
        $customer = $this->shopperHandler->getCustomerData(['id' => $payload->data->metadata->customerId]);

        // Save the card
        return $this->vaultHandler->setCardToken($payload->data->source->id)->setCustomerId(
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
