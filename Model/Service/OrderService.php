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

use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Helper\Data;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\HTTP\ZendClient;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Observer\DataAssignObserver;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class OrderService {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var AgreementsValidatorInterface
     */
    private $agreementsValidator;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Data
     */
    private $checkoutHelper;

    /**
     * @var Order
     */
    private $orderManager;

    /**
     * @var Repository
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * OrderService constructor.
     * @param CartManagementInterface $cartManagement
     * @param AgreementsValidatorInterface $agreementsValidator
     * @param Session $customerSession
     * @param Data $checkoutHelper
     * @param Order $orderManager
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        AgreementsValidatorInterface $agreementsValidator,
        Session $customerSession,
        Data $checkoutHelper,
        Order $orderManager,
        Repository $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        TransferFactory $transferFactory,
        GatewayConfig $gatewayConfig,
        Watchdog $watchdog
    ) {
        $this->cartManagement        = $cartManagement;
        $this->agreementsValidator   = $agreementsValidator;
        $this->customerSession       = $customerSession;
        $this->checkoutHelper        = $checkoutHelper;
        $this->orderManager          = $orderManager;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder         = $filterBuilder;
        $this->transferFactory       = $transferFactory;
        $this->gatewayConfig         = $gatewayConfig;
        $this->watchdog              = $watchdog;
    }

    /**
     * Runs the service.
     *
     * @param Quote $quote
     * @param $cardToken
     * @param array $agreement
     * @throws LocalizedException
     */
    public function execute(Quote $quote, $cardToken, array $agreement) {
        if (!$this->agreementsValidator->isValid($agreement)) {
            throw new LocalizedException(__('Please agree to all the terms and conditions before placing the order.'));
        }

        if ($this->getCheckoutMethod($quote) === Onepage::METHOD_GUEST) {
            $this->prepareGuestQuote($quote);
        }

        // Set up the payment information
        $payment = $quote->getPayment();
        $payment->setMethod(ConfigProvider::CODE);

        // Card token always pressent, except for alternative payments
        if ($cardToken) {
            $payment->setAdditionalInformation(DataAssignObserver::CARD_TOKEN_ID, $cardToken);
        }

        // Configure the quote
        $this->disabledQuoteAddressValidation($quote);
        $quote->collectTotals();

        // Place the order
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        return $orderId;
    }

    public function cancelTransactionToRemote(Order $order) {
        // Get the transaction data
        $transactionData = $this->_getTransactionsData($order);

        // Prepare url prefix
        $url = 'charges/';

        //  Process the request
        if ($transactionData) {
            // Check if transaction is capture or authorisation
            if ($transactionData['txnType'] == 'capture') {
                $url .= $transactionData['txnId'] . '/refund';
            }
            else if ($transactionData['txnType'] == 'authorization') {
                $url .= $transactionData['txnId'] . '/void';
            }

            // Launch the query
            $method = 'POST';
            $transfer = $this->transferFactory->create([
                'trackId'   => $order->getIncrementId()
            ]);

            // Handle the request
            $this->_handleRequest($url, $method, $transfer);
        }
    }

    public function cancelTransactionFromRemote(Order $order) {
        if ($order->canCancel()) {
            try {
                // Cancel the order
                $order->cancel()->save();
                
                // Add a comment to history
                $order->addStatusToHistory($order->getStatus(), __('Order and transaction cancelled'), $notify = true);
                $order->save();
            }
            catch (Zend_Http_Client_Exception $e) {
                throw new ClientException(__($e->getMessage()));
            }
        }
    }

    protected function _getTransactionsData(Order $order) {
        // Get transactions for the order
        $transactions = $this->_getOrderTransactions($order);

        // Count the transactions in the order
        $tnxCount = count($transactions);

        // Prepare the result
        $result = false;

        if ($tnxCount == 1) {
            // For each transaction
            foreach ($transactions as $transaction) {
                if ($transaction->getTxnType() == 'authorization') {
                    $result = array(
                        'txnType' => $transaction->getTxnType(),
                        'txnId' => $transaction->getTxnId()
                    ); 
                }
            }
        }
        else {
            // For each transaction
            foreach ($transactions as $transaction) {
                if ($transaction->getTxnType() == 'capture') {
                    $result = array(
                        'txnType' => $transaction->getTxnType(),
                        'txnId' => $transaction->getTxnId()
                    ); 
                }
            }
        }

        return $result;
    }

    protected function _handleRequest($url, $method, $transfer) {        
        try {
            // Send the request
            $response           = $this->getHttpClient($url, $transfer)->request();
            $result             = json_decode($response->getBody(), true);

            // Handle the response
            $this->_handleResponse($result);
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }
    }

    protected function _handleResponse($response) {
        // Debug info
        $this->watchdog->bark($response);
    }

    protected function _getOrderTransactions(Order $order) {
        // Payment filter
        $filters[] = $this->filterBuilder->setField('payment_id')
        ->setValue($order->getPayment()->getId())
        ->create();

        // Order filter
        $filters[] = $this->filterBuilder->setField('order_id')
        ->setValue($order->getId())
        ->create();

        // Build the search criteria
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
        ->create();
        
        return $this->transactionRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Get checkout method.
     *
     * @param Quote $quote
     * @return string
     */
    private function getCheckoutMethod(Quote $quote) {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }

        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }

        return $quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit.
     *
     * @param Quote $quote
     * @return void
     */
    private function prepareGuestQuote(Quote $quote) {
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
    }

    /**
     * Disables the address validation for the given quote instance.
     *
     * @param Quote $quote
     */
    protected function disabledQuoteAddressValidation(Quote $quote) {
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setShouldIgnoreValidation(true);

        if (!$quote->getIsVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShouldIgnoreValidation(true);

            if (!$billingAddress->getEmail()) {
                $billingAddress->setSameAsBilling(1);
            }
        }
    }

    /**
     * Returns prepared HTTP client.
     *
     * @param string $endpoint
     * @param TransferInterface $transfer
     * @return ZendClient
     * @throws \Exception
     */
    private function getHttpClient($endpoint, TransferInterface $transfer) {
        $client = new ZendClient($this->gatewayConfig->getApiUrl() . $endpoint);
        $client->setMethod('POST');
        $client->setRawData( json_encode( $transfer->getBody()) ) ;
        $client->setHeaders($transfer->getHeaders());
        $client->setUrlEncodeBody($transfer->shouldEncode());
        
        return $client;
    }
}
