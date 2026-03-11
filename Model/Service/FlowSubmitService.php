<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
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

use Checkout\Payments\Sessions\PaymentSessionSubmitRequest;
use CheckoutCom\Magento2\Model\Formatter\PriceFormatter;
use CheckoutCom\Magento2\Provider\AccountSettings;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Submits a Flow payment session with reference (order increment id) so Checkout.com receives it.
 */
class FlowSubmitService
{
    private const EURO_CURRENCY_CODE = 'EUR';
    private const PAYMENT_TYPE_REGULAR = 'Regular';

    private AccountSettings $accountSettings;
    private ApiHandlerService $apiHandler;
    private CheckoutSession $checkoutSession;
    private FlowGeneralSettings $flowGeneralSettings;
    private LoggerInterface $logger;
    private PriceFormatter $priceFormatter;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ApiHandlerService $apiHandler,
        AccountSettings $accountSettings,
        CheckoutSession $checkoutSession,
        FlowGeneralSettings $flowGeneralSettings,
        LoggerInterface $logger,
        PriceFormatter $priceFormatter,
        StoreManagerInterface $storeManager
    ) {
        $this->apiHandler = $apiHandler;
        $this->accountSettings = $accountSettings;
        $this->checkoutSession = $checkoutSession;
        $this->flowGeneralSettings = $flowGeneralSettings;
        $this->logger = $logger;
        $this->priceFormatter = $priceFormatter;
        $this->storeManager = $storeManager;
    }

    /**
     * Submit payment session to Checkout.com with session_data and reference (order id).
     *
     * @throws Exception
     */
    public function submit(string $sessionId, string $sessionData, string $reference): array
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getEntityId()) {
            throw new Exception(__('Order not found. Please try again.')->render());
        }

        try {
            $storeCode = $this->storeManager->getStore()->getCode();
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (Exception $exception) {
            $websiteCode = null;
            $storeCode = null;
            $this->logger->error(sprintf('%s: %s', __METHOD__, $exception->getMessage()));
        }

        if (!$this->flowGeneralSettings->useFlow($websiteCode)) {
            throw new Exception(__('Flow is not enabled for this website.')->render());
        }

        $secretKey = $this->accountSettings->getSecretKey($websiteCode);
        $publicKey = $this->accountSettings->getPublicKey($websiteCode);
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE, $secretKey, $publicKey);

        $currency = $order->getOrderCurrencyCode() ?? $order->getBaseCurrencyCode() ?? self::EURO_CURRENCY_CODE;
        $amount = $this->priceFormatter->getFormattedPrice(
            (float)$order->getGrandTotal(),
            $currency
        );

        $request = new PaymentSessionSubmitRequest();
        $request->session_data = $sessionData;
        $request->amount = (int)round($amount);
        $request->reference = $reference;
        $request->payment_type = self::PAYMENT_TYPE_REGULAR;

        $client = $api->getCheckoutApi()->getPaymentSessionsClient();

        return $client->submitPaymentSession($sessionId, $request);
    }
}
