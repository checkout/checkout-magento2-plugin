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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Service;

/**
 * Class WebhookHandlerService.
 */
class WebhookHandlerService
{
    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * WebhookHandlerService constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler
    ) {
        $this->transactionHandler = $transactionHandler;
    }


    /**
     * handle an incoming webhook.
     */
    public function handleWebhook($order, $transactionType, $data = null)
    {
        // Prepare parameters
        $this->setProperties($order);

        // Process the transaction
        switch ($transactionType) {
            case Transaction::TYPE_AUTH:
                $this->handleAuthorization($transactionType, $data);
                break;

            case Transaction::TYPE_CAPTURE:
                $this->handleCapture($transactionType, $data);
                break;

            case Transaction::TYPE_VOID:
                $this->handleVoid($transactionType, $data);
                break;

            case Transaction::TYPE_REFUND:
                $this->handleRefund($transactionType, $data);
                break;

            default:
                $this->handleEvent($data);
        }

        // Return the order
        return $this->order;
    }

    /**
     * Convert a gateway to decimal value for processing.
     */
    public function amountFromGateway($amount, $order)
    {
        // Get the quote currency
        $currency = $order->getOrderCurrencyCode();

        // Get the x1 currency calculation mapping
        $currenciesX1 = explode(
            ',',
            $this->config->getValue('currencies_x1')
        );

        // Get the x1000 currency calculation mapping
        $currenciesX1000 = explode(
            ',',
            $this->config->getValue('currencies_x1000')
        );

        // Prepare the amount
        if (in_array($currency, $currenciesX1)) {
            return $amount;
        } elseif (in_array($currency, $currenciesX1000)) {
            return $amount/1000;
        } else {
            return $amount/100;
        }
    }
}
