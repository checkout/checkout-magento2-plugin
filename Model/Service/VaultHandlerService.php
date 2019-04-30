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
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;

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
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

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
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->vaultTokenFactory = $vaultTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSession;
        $this->remoteAddress = $remoteAddress;
        $this->config = $config;
    }

    /**
     * Returns the payment token instance if exists.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return PaymentTokenInterface|null
     */
    private function foundExistedPaymentToken(PaymentTokenInterface $paymentToken) {
        return $this->paymentTokenManagement->getByPublicHash($paymentToken->getPublicHash(), $paymentToken->getCustomerId() );
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
        $this->cardToken = $cardToken;

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
        try {
            // Set the token source
            $tokenSource = new TokenSource($this->cardToken);

            // Set the payment
            $request = new Payment(
                $tokenSource, 
                $this->config->getValue('request_currency', 'checkoutcom_vault')
            );

            // Set the request parameters
            $request->capture = false;
            $request->amount = 0;
            $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
            $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
            $request->threeDs = new ThreeDs($this->config->needs3ds('checkoutcom_vault'));
            $request->threeDs->attempt_n3d = (bool) $this->config->getValue('attempt_n3d', 'checkoutcom_vault');
            $request->description = __('Save card authorization request from %1', $this->config->getStoreName());
            $request->payment_ip = $this->remoteAddress->getRemoteAddress();

            // Send the charge request
            $response = $this->apiHandler->checkoutApi
                ->payments()
                ->request($request);

            // Todo - remove logging code
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/vault_response.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($response, 1));

            //$this->authorizedResponse = $result;
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
     * Checks if a user has saved cards.
     */
    public function userHasCards() {
        // Get the customer id (currently logged in user)
        $customerId = $this->customerSession->getCustomer()->getId();   
        
        if ((int) $customerId > 0) {
            // Get the card list
            $cardList = $this->paymentTokenManagement->getListByCustomerId($customerId);
            if (count($cardList) > 0) {
                return  true;
            }
        }

        return false;
    }
}