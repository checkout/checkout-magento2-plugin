<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use CheckoutCom\Magento2\Gateway\Http\Client;
use CheckoutCom\Magento2\Gateway\Config\Config;

class PaymentTokenService {

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Config
     */
    protected $config;

    /**
     * PaymentTokenService constructor.
     */
    public function __construct(
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Client $client,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->storeManager  = $storeManager;
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * PaymentTokenService Constructor.
     */
    public function getToken() {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'tokens/payment';

        // Get the currency code
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        // Get the quote object
        $quote = $this->checkoutSession->getQuote();

        // Get the quote amount
        $amount = ChargeAmountAdapter::getPaymentFinalCurrencyValue($quote->getGrandTotal());

        if ((float) $amount >= 0 && !empty($amount)) {
            // Prepare the amount 
            $value = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);

            // Prepare the transfer data
            $params = [
                'value' => $value,
                'currency' => $currencyCode,
                'trackId' => $quote->reserveOrderId()->save()->getReservedOrderId()
            ];

            // Send the request
            $response = $this->client->post($url, $params);
            $response = isset($response) ? (array) json_decode($response) : null;

            // Extract the payment token
            if (isset($response['id'])){
                return $response['id'];
            }
        }

        return false;
    }

    public function sendChargeRequest($cardToken, $order) {
        // Set the request parameters
        $url = $this->config->getApiUrl() . 'charges/token';
        $params = [
            'autoCapTime'   => $this->config->getAutoCaptureTimeInHours(),
            'autoCapture'   => $this->config->isAutoCapture() ? 'Y' : 'N',
            'email'         => $order->getBillingAddress()->getEmail(),
            'customerIp'    => $order->getRemoteIp(),
            'chargeMode'    => $this->config->isVerify3DSecure() ? 2 : 1,
            'attemptN3D'    => filter_var($this->config->isAttemptN3D(), FILTER_VALIDATE_BOOLEAN),
            'customerName'  => $order->getCustomerName(),
            'currency'      => ChargeAmountAdapter::getPaymentFinalCurrencyCode($order->getCurrencyCode()),
            'value'         => $order->getGrandTotal()*100,
            'trackId'       => $order->getIncrementId(),
            'cardToken'     => $cardToken
        ];

        // Handle the request
        $response = $this->client->post($url, $params);

        // Return the response
        return $response;
    }
}
