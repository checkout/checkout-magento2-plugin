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

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Request\PostPaymentSessions;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\FlowMethodSettings;
use CheckoutCom\Magento2\Provider\GeneralSettings;
use Exception;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class FlowPrepareService
{
    protected PostPaymentSessions $postPaymentSession;
    protected StoreManagerInterface $storeManager;
    protected ApiHandlerService $apiHandler;
    protected AccountSettings $accountConfiguration;
    protected FlowMethodSettings $flowMethodConfiguration;
    protected GeneralSettings $generalConfiguration;
    protected LoggerInterface $logger;
    protected Logger $ckoLogger;
    protected Utilities $utilities;

    public function __construct(
        ApiHandlerService $apiHandler,
        PostPaymentSessions $postPaymentSession,
        StoreManagerInterface $storeManager,
        AccountSettings $accountConfiguration,
        FlowMethodSettings $flowMethodConfiguration,
        GeneralSettings $generalConfiguration,
        LoggerInterface $logger,
        Logger $ckoLogger,
        Utilities $utilities
    ) {
        $this->postPaymentSession = $postPaymentSession;
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->accountConfiguration = $accountConfiguration;
        $this->flowMethodConfiguration = $flowMethodConfiguration;
        $this->generalConfiguration = $generalConfiguration;
        $this->logger = $logger;
        $this->ckoLogger = $ckoLogger;
        $this->utilities = $utilities;
    }

    public function prepare(CartInterface $quote, array $data)
    {
        try {
            $storeCode = $this->storeManager->getStore()->getCode();
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (Exception $error) {
            $websiteCode = null;
            $storeCode = null;

            $this->logger->error(
                sprintf("Unable to fetch store code or website code: %s", $error->getMessage()),
            );
        }

        $secretKey = $this->accountConfiguration->getSecretKey($websiteCode);
        $publicKey = $this->accountConfiguration->getPublicKey($websiteCode);
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE, $secretKey, $publicKey);

        $payload = $this->postPaymentSession->get($quote, $data);

        try {
            $this->ckoLogger->additional($this->utilities->objectToArray($payload), 'payment');
            $responseAPI = $api->getCheckoutApi()->getPaymentSessionsClient()->createPaymentSessions($payload);
        } catch (Exception $error) {
            $this->logger->error(
                sprintf("Error during API call: %s", $error->getMessage()),
            );

            return [
                'error' => true
            ];
        }

        $response = [
            'appearance' => $this->flowMethodConfiguration->getDesign($storeCode),
            'environment' => $this->generalConfiguration->isProductionModeEnabled(null) ? "production" : "sandbox",
            'paymentSession' => $responseAPI ?? '',
            'publicKey' => $publicKey
        ];
        return $response;
    }
}
