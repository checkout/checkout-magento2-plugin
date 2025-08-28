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

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Model\Request\PostPaymentSessions;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\GeneralSettings;
use CheckoutCom\Magento2\Provider\FlowMethodSettings;
use Exception;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

class FlowPrepareService
{
    protected PostPaymentSessions $postPaymentSession;
    protected StoreManagerInterface $storeManager;
    protected ApiHandlerService $apiHandler;
    protected AccountSettings $accountConfiguration;
    protected FlowMethodSettings $flowMethodConfiguration;
    protected GeneralSettings $generalConfiguration;

    public function __construct(
        ApiHandlerService $apiHandler,
        PostPaymentSessions $postPaymentSession,
        StoreManagerInterface $storeManager,
        AccountSettings $accountConfiguration,
        FlowMethodSettings $flowMethodConfiguration,
        GeneralSettings $generalConfiguration
    ) {
        $this->postPaymentSession = $postPaymentSession;
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->accountConfiguration = $accountConfiguration;
        $this->flowMethodConfiguration = $flowMethodConfiguration;
        $this->generalConfiguration = $generalConfiguration;
    }

    public function prepare(CartInterface $quote, array $data) {

        $storeCode = $this->storeManager->getStore()->getCode();
        $secretKey = $this->accountConfiguration->getSecretKey(null);
        $publicKey = $this->accountConfiguration->getPublicKey(null);
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE, $secretKey, $publicKey);

        $payload = $this->postPaymentSession->get($quote, $data);
        
        try {
            $responseAPI = $api->getCheckoutApi()->getPaymentSessionsClient()->createPaymentSessions($payload);
        } catch (Exception $error) {
            return [
                'error' => true
            ];
        }

        $response = [
            'appearance' => $this->flowMethodConfiguration->getDesign($storeCode),
            'environment' => $this->generalConfiguration->isProductionModeEnabled(null) ? "production" : "sandbox",
            'paymentSession' => isset($responseAPI['payment_session_token']) ? $responseAPI['payment_session_token'] : '',
            'publicKey' => $publicKey
        ];
        return $response;
    }
}
