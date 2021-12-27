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
use Magento\PageCache\Model\Cache\Type;
use Checkout\Models\Webhooks\Webhook as WebhookModel;

/**
 * Class Webhook
 *
 * @category  Magento2
 * @package   Checkout.com
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
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ApiHandlerService $apiHandler,
        Config $resourceConfig,
        TypeListInterface $cacheTypeList,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiHandler        = $apiHandler;
        $this->resourceConfig    = $resourceConfig;
        $this->cacheTypeList     = $cacheTypeList;
        $this->logger            = $logger;
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
            $secretKey  = $this->getRequest()->getParam('secret_key', 0);
            $publicKey  = $this->getRequest()->getParam('public_key', 0);
            $webhookUrl = $this->getRequest()->getParam('webhook_url', 0);

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, $scope, $secretKey);

            $events     = $api->checkoutApi->events()->types(['version' => '2.0']);
            $eventTypes = $events->list[0]->event_types;
            $webhooks   = $api->checkoutApi->webhooks()->retrieve();
            $webhookId  = null;
            foreach ($webhooks->list as $list) {
                if ($list->url == $webhookUrl) {
                    $webhookId = $list->id;
                }
            }

            if (isset($webhookId)) {
                $webhook              = new WebhookModel($webhookUrl, $webhookId);
                $webhook->event_types = $eventTypes;
                $response             = $api->checkoutApi->webhooks()->update($webhook, true);
            } else {
                $webhook  = new WebhookModel($webhookUrl);
                $response = $api->checkoutApi->webhooks()->register($webhook, $eventTypes);
            }

            $privateSharedKey = $response->headers->authorization;

            $this->resourceConfig->saveConfig(
                'settings/checkoutcom_configuration/private_shared_key',
                $response->headers->authorization,
                $scope,
                $storeCode
            );
            $this->resourceConfig->saveConfig(
                'settings/checkoutcom_configuration/secret_key',
                $secretKey,
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
                'privateSharedKey' => isset($privateSharedKey) ? $privateSharedKey : '',
                'message'          => 'Could not set webhooks, please check your account settings',
            ]);
        }
    }
}
