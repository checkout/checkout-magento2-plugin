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

namespace CheckoutCom\Magento2\Controller\Adminhtml\System\Config;

use Checkout\CheckoutApiException;
use Checkout\CheckoutAuthorizationException;
use Checkout\Webhooks\Previous\WebhookRequest;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\PageCache\Model\Cache\Type;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Webhook
 */
class Webhook extends Action
{
    private JsonFactory $resultJsonFactory;
    private ApiHandlerService $apiHandler;
    private Config $resourceConfig;
    private TypeListInterface $cacheTypeList;
    private Logger $logger;
    private ScopeConfigInterface $scopeConfig;
    private EncryptorInterface $encryptor;

    public function __construct(
        JsonFactory $resultJsonFactory,
        ApiHandlerService $apiHandler,
        Config $resourceConfig,
        TypeListInterface $cacheTypeList,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        Context $context,
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiHandler = $apiHandler;
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * Main controller function.
     *
     * @return Json
     */
    public function execute()
    {
        try {
            // Prepare some parameters
            $message = '';
            // Get the store code
            $scope = $this->getRequest()->getParam('scope', 0);
            $storeCode = $this->getRequest()->getParam('scope_id', 0);
            $publicKey = $this->getRequest()->getParam('public_key', 0);
            $webhookUrl = $this->getRequest()->getParam('webhook_url', 0);
            $secretKey = $this->getRequest()->getParam('secret_key', 0)
                ?: $this->scopeConfig->getValue('settings/checkoutcom_configuration/secret_key', ScopeInterface::SCOPE_WEBSITE);

            // Initialize the API handler
            $checkoutApi = $this->apiHandler
                ->init($storeCode, $scope, $secretKey, $publicKey)
                ->getCheckoutApi();

            $events = $checkoutApi->getWebhooksClient()->retrieveWebhooks();
            if ($this->apiHandler->isValidResponse($events)) {
                $eventTypes = $events['items'][0]['event_types'];
                $webhooks = $checkoutApi->getWebhooksClient()->retrieveWebhooks();
                if ($this->apiHandler->isValidResponse($webhooks)) {
                    $webhookId = null;
                    foreach ($webhooks['items'] as $list) {
                        if ($list['url'] === $webhookUrl) {
                            $webhookId = $list['id'];
                        }
                    }

                    $webhookRequest = new WebhookRequest();
                    $webhookRequest->event_types = $eventTypes;
                    $webhookRequest->url = $webhookUrl;

                    if (isset($webhookId)) {
                        $response = $checkoutApi->getWebhooksClient()->updateWebhook($webhookId, $webhookRequest);
                    } else {
                        $webhookRequest->active = true;
                        $response = $checkoutApi->getWebhooksClient()->registerWebhook($webhookRequest);
                    }

                    $success = $this->apiHandler->isValidResponse($response);

                    if ($success) {
                        $privateSharedKey = $response['headers']['authorization'];
                        $encryptedPrivateSharedKey = $this->encryptor->encrypt($privateSharedKey);

                        $this->resourceConfig->saveConfig(
                            'settings/checkoutcom_configuration/private_shared_key',
                            $encryptedPrivateSharedKey,
                            $scope,
                            $storeCode
                        );
                        $this->resourceConfig->saveConfig(
                            'settings/checkoutcom_configuration/public_key',
                            $publicKey,
                            $scope,
                            $storeCode
                        );

                        $this->cacheTypeList->cleanType(CacheTypeConfig::TYPE_IDENTIFIER);
                        $this->cacheTypeList->cleanType(Type::TYPE_IDENTIFIER);
                    }
                }
            }
        } catch (CheckoutApiException | CheckoutAuthorizationException $e) {
            $success = false;
            $message = __($e->getMessage());
            $this->logger->write($message);
        } finally {
            return $this->resultJsonFactory->create()->setData([
                'success' => $success,
                'privateSharedKey' => $privateSharedKey ?? '',
                'message' => 'Could not set webhooks, please check your account settings',
            ]);
        }
    }
}
