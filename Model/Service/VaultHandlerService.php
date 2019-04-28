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

use Magento\Vault\Api\Data\PaymentTokenInterface;

class VaultHandlerService {

    /**
     * @var VaultTokenFactory
     */
    protected $vaultTokenFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var string
     */
    protected $customerEmail;

    /**
     * @var int
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $cardToken;

    /**
     * @var array
     */
    protected $cardData = [];

    /**
     * @var array
     */
    protected $authorizedResponse = [];
    
    /**
     * VaultHandlerService constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Factory\VaultTokenFactory $vaultTokenFactory,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Customer\Model\Session $customerSession,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->vaultTokenFactory = $vaultTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->config = $config;
    }

    /**
     * Sets the customer ID.
     *
     * @param int $customerId
     * @return VaultHandlerService
     */
    public function setCustomerId($id = null) {
        $this->customerId = (int) $id > 0 ? $id : $this->customerSession->getCustomer()->getId();

        return $this;
    }

    /**
     * Sets the customer email address.
     *
     * @param string $customerEmail
     * @return VaultHandlerService
     */
    public function setCustomerEmail() {
        $this->customerEmail = $this->customerSession->getCustomer()->getEmail();

        return $this;
    }

    /**
     * Sets the card token.
     *
     * @param string $cardToken
     * @return VaultHandlerService
     */
    public function setCardToken($cardToken) {
        $this->cardToken    = $cardToken;

        return $this;
    }


    /**
     * Sets the card data.
     *
     * @return VaultHandlerService
     */
    public function setCardData() {

        // Prepare the card data to save
        $cardData = $this->authorizedResponse['card'];
        unset($cardData['customerId']);
        unset($cardData['billingDetails']);
        unset($cardData['bin']);
        unset($cardData['fingerprint']);
        unset($cardData['cvvCheck']);
        unset($cardData['name']);
        unset($cardData['avsCheck']);

        // Assign the card data
        $this->cardData = $cardData;

        return $this;
    }

    /**
     * Tests the card through gateway.
     *
     * @return VaultHandlerService
     */
    public function test() {

        // Perform the authorization
        $this->authorizeTransaction();

        // Validate the authorization
        $this->validateAuthorization();

        // Perform the void
        $this->voidTransaction();

        return $this;
    }

    /**
     * Saves the credit card in the repository.
     *
     * @throws LocalizedException
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    public function save() {
        // Create the payment token from response
        $paymentToken = $this->vaultTokenFactory->create($this->cardData, $this->customerId);
        $foundPaymentToken  = $this->foundExistedPaymentToken($paymentToken);

        // Check if card exists
        if ($foundPaymentToken) {
            if ($foundPaymentToken->getIsActive()) {
                $this->messageManager->addNoticeMessage(__('The credit card has been stored already.'));
            }

            // Activate or reactivate the card
            $foundPaymentToken->setIsActive(true);
            $foundPaymentToken->setIsVisible(true);
            $this->paymentTokenRepository->save($foundPaymentToken);
        }

        // Otherwise save the card
        else {
            $gatewayToken = $this->authorizedResponse['card']['id'];
            $paymentToken->setGatewayToken($gatewayToken);
            $paymentToken->setIsVisible(true);
            $this->paymentTokenRepository->save($paymentToken);
        }
    }

    /**
     * Runs the authorization command for the gateway.
     *
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    private function authorizeTransaction() {

        $requestUri = 'charges/token'; // todo - get this url from http client class

        $transfer = $this->transferFactory->create([
            'autoCapture'   => 'N',
            'description'   => 'Saving new card',
            'value'         => (float) $this->scopeConfig->getValue('payment/checkout_com/save_card_check_amount') * 100,
            'currency'      => $this->scopeConfig->getValue('payment/checkout_com/save_card_check_currency'),
            'cardToken'     => $this->cardToken,
            'email'         => $this->customerEmail,
        ]);
        
        $log = [
            'request'           => $transfer->getBody(),
            'request_uri'       => $requestUri,
            'request_headers'   => $transfer->getHeaders(),
            'request_method'    => 'POST',
        ];

        try {
            $response           = $this->getHttpClient($requestUri, $transfer)->request();
            
            $result             = json_decode($response->getBody(), true);
            $log['response']    = $result;

            // Outpout the response in debug mode
            $this->watchdog->bark($result);


            if( array_key_exists('errorCode', $result) ) {
                throw new ApiClientException($result['message'], $result['errorCode'], $result['eventId']);
            }

            $this->authorizedResponse = $result;
        }
        catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Validates the authorization response.
     *
     * @throws LocalizedException
     */
    private function validateAuthorization() {
        if( array_key_exists('status', $this->authorizedResponse) AND $this->authorizedResponse['status'] === 'Declined') {
            throw new LocalizedException(__('The transaction has been declined.'));
        }
    }

    /**
     * Runs the void command for the gateway.
     *
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    private function voidTransaction() {
        $transactionId  = $this->authorizedResponse['id'];
        $transfer       = $this->transferFactory->create([
            'trackId'   => ''
        ]);

        $chargeUrl = 'charges/' . $transactionId . '/void';

        $log = [
            'request'           => $transfer->getBody(),
            'request_uri'       => $chargeUrl,
            'request_headers'   => $transfer->getHeaders(),
            'request_method'    => 'POST',
        ];

        try {
            $response           = $this->getHttpClient($chargeUrl, $transfer)->request();
           
            $result             = json_decode($response->getBody(), true);
            $log['response']    = $result;

            if( array_key_exists('errorCode', $result) ) {
                throw new ApiClientException($result['message'], $result['errorCode'], $result['eventId']);
            }
        }
        catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Returns the payment token instance if exists.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return PaymentTokenInterface|null
     */
    private function foundExistedPaymentToken(PaymentTokenInterface $paymentToken) {
        return $this->paymentTokenManagement->getByPublicHash( $paymentToken->getPublicHash(), $paymentToken->getCustomerId() );
    }
}