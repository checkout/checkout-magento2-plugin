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

namespace Checkoutcom\Magento2\Controller\Adminhtml\System\Config;

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Exception;
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
use Checkout\Models\Webhooks\Webhook as WebhookModel;

/**
 * Class Webhook
 */
class Webhook extends Action
{
    protected $resultJsonFactory;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;
    /**
     * $resourceConfig field
     *
     * @var Config $resourceConfig
     */
    private $resourceConfig;
    /**
     * $cacheTypeList field
     *
     * @var TypeListInterface $cacheTypeList
     */
    private $cacheTypeList;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;

    /**
     * Webhook constructor
     *
     * @param Context              $context
     * @param JsonFactory          $resultJsonFactory
     * @param ApiHandlerService    $apiHandler
     * @param Config               $resourceConfig
     * @param TypeListInterface    $cacheTypeList
     * @param Logger               $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ApiHandlerService $apiHandler,
        Config $resourceConfig,
        TypeListInterface $cacheTypeList,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiHandler        = $apiHandler;
        $this->resourceConfig    = $resourceConfig;
        $this->cacheTypeList     = $cacheTypeList;
        $this->logger            = $logger;
        $this->scopeConfig       = $scopeConfig;
        $this->encryptor         = $encryptor;

        parent::__construct($context);
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
            $scope      = $this->getRequest()->getParam('scope', 0);
            $storeCode  = $this->getRequest()->getParam('scope_id', 0);
            $publicKey  = $this->getRequest()->getParam('public_key', 0);
            $webhookUrl = $this->getRequest()->getParam('webhook_url', 0);
            $secretKey  = $this->scopeConfig->getValue('settings/checkoutcom_configuration/secret_key')
            ?: $this->getRequest()->getParam('secret_key', 0);


            // Initialize the API handler
            $checkoutApi = $this->apiHandler
                ->init($storeCode, $scope, $secretKey)
                ->getCheckoutApi();

            $events     = $checkoutApi->events()->types(['version' => '2.0']);
            $eventTypes = $events->list[0]->event_types;
            $webhooks   = $checkoutApi->webhooks()->retrieve();
            $webhookId  = null;
            foreach ($webhooks->list as $list) {
                if ($list->url == $webhookUrl) {
                    $webhookId = $list->id;
                }
            }

            if (isset($webhookId)) {
                $webhook              = new WebhookModel($webhookUrl, $webhookId);
                $webhook->event_types = $eventTypes;
                $response             = $checkoutApi->webhooks()->update($webhook, true);
            } else {
                $webhook  = new WebhookModel($webhookUrl);
                $response = $checkoutApi->webhooks()->register($webhook, $eventTypes);
            }

            $privateSharedKey = $response->headers->authorization;
            /** @var string $encryptedPrivateSharedKey */
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

            $success = $response->isSuccessful();

            $this->cacheTypeList->cleanType(CacheTypeConfig::TYPE_IDENTIFIER);
            $this->cacheTypeList->cleanType(Type::TYPE_IDENTIFIER);
        } catch (Exception $e) {
            $success = false;
            $message = __($e->getMessage());
            $this->logger->write($message);
        } finally {
            return $this->resultJsonFactory->create()->setData([
                'success'          => $success,
                'privateSharedKey' => $privateSharedKey ?? '',
                'message'          => 'Could not set webhooks, please check your account settings',
            ]);
        }
    }
}
