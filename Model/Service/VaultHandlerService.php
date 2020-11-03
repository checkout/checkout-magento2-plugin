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

use Magento\Vault\Api\Data\PaymentTokenInterface;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;

/**
 * Class VaultHandlerService.
 */
class VaultHandlerService
{
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var VaultToken
     */
    public $vaultToken;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    public $paymentTokenRepository;

    /**
     * @var PaymentTokenManagementInterface
     */
    public $paymentTokenManagement;

    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var ApiHandlerService
     */
    public $apiHandlerService;

    /**
     * @var CardHandlerService
     */
    public $cardHandler;

    /**
     * @var string
     */
    public $customerEmail;

    /**
     * @var int
     */
    public $customerId;

    /**
     * @var string
     */
    public $cardToken;

    /**
     * @var array
     */
    public $cardData = [];

    /**
     * @var array
     */
    public $response = [];

    /**
     * VaultHandlerService constructor.
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Vault\VaultToken $vaultToken,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->vaultToken = $vaultToken;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->apiHandler = $apiHandler;
        $this->cardHandler = $cardHandler;
        $this->config = $config;
    }

    /**
     * Returns the payment token instance if exists.
     *
     * @param  PaymentTokenInterface $paymentToken
     * @return PaymentTokenInterface|null
     */
    private function foundExistedPaymentToken(PaymentTokenInterface $paymentToken)
    {
        return $this->paymentTokenManagement->getByPublicHash(
            $paymentToken->getPublicHash(),
            $paymentToken->getCustomerId()
        );
    }

    /**
     * Sets the customer ID.
     *
     * @param  int $customerId
     * @return VaultHandlerService
     */
    public function setCustomerId($id = null)
    {
        $this->customerId = (int) $id > 0
        ? $id : $this->customerSession->getCustomer()->getId();

        return $this;
    }

    /**
     * Sets the customer email address.
     *
     * @param  string $customerEmail
     * @return VaultHandlerService
     */
    public function setCustomerEmail($email = null)
    {
        $this->customerEmail = ($email)
        ? $email : $this->customerSession->getCustomer()->getEmail();

        return $this;
    }

    /**
     * Sets the card token.
     *
     * @param  string $cardToken
     * @return VaultHandlerService
     */
    public function setCardToken($cardToken)
    {
        $this->cardToken = $cardToken;

        return $this;
    }

    /**
     * Sets a gateway response if no prior card authorization is needed.
     */
    public function setResponse($response)
    {
        $this->response = $response;

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
    public function save()
    {
        // Create the payment token from response
        $paymentToken = $this->vaultToken->create(
            $this->cardData,
            $this->customerId
        );
        $foundPaymentToken  = $this->foundExistedPaymentToken($paymentToken);

        // Check if card exists
        if ($foundPaymentToken) {
            // Display a message if the card exists
            if ($foundPaymentToken->getIsActive() && $foundPaymentToken->getIsVisible()) {
                $this->messageManager->addNoticeMessage(__('This card is already saved.'));
            }

            // Activate or reactivate the card
            $foundPaymentToken->setIsActive(true);
            $foundPaymentToken->setIsVisible(true);
            $this->paymentTokenRepository->save($foundPaymentToken);
        } else {
            // Otherwise save the card
            $gatewayToken = $this->response['card']['id'];
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
    public function authorizeTransaction()
    {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        // Set the token source
        $tokenSource = new TokenSource($this->cardToken);

        // Set the payment
        $request = new Payment(
            $tokenSource,
            $this->config->getValue('request_currency', 'checkoutcom_vault')
        );

        // Set the request parameters
        $request->amount = 0;
        $request->threeDs = new ThreeDs($this->config->needs3ds('checkoutcom_vault'));
        $request->threeDs->attempt_n3d = (bool) $this->config->getValue('attempt_n3d', 'checkoutcom_vault');
        // $request->description = __('Save card authorization request from %1', $this->config->getStoreName());
        $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';

        // Send the charge request and get the response
        $this->response = $api->checkoutApi
            ->payments()
            ->request($request);

        return $this;
    }

    /**
     * Validates the authorization response.
     *
     * @throws LocalizedException
     */
    public function saveCard()
    {
        // Initialize the API handler
        $api = $this->apiHandler->init();

        // Check if the response is success
        $success = $api->isValidResponse($this->response);
        if ($success) {
            // Get the response array
            $values = $this->response->getValues();
            if (isset($values['source'])) {
                // Get the card data
                $cardData = $values['source'];

                // Create the payment token
                $paymentToken = $this->vaultToken->create($cardData, 'checkoutcom_vault', $this->customerId);
                $foundPaymentToken = $this->foundExistedPaymentToken($paymentToken);

                // Check if card exists
                if ($foundPaymentToken) {
                    // Activate or reactivate the card
                    $foundPaymentToken->setIsActive(true);
                    $foundPaymentToken->setIsVisible(true);
                    $this->paymentTokenRepository->save($foundPaymentToken);
                } else {
                    // Otherwise save the card
                    $gatewayToken = $cardData['id'];
                    $paymentToken->setGatewayToken($gatewayToken);
                    $paymentToken->setIsVisible(true);
                    $this->paymentTokenRepository->save($paymentToken);
                }
            }
        }

        return $success;
    }

    /**
     * Checks if a user has saved cards.
     */
    public function userHasCards($customerId = null)
    {
        // Get the card list
        $cardList = $this->getUserCards($customerId);

        // Check if the user has cards
        if (!empty($cardList)) {
            return  true;
        }

        return false;
    }

    /**
     * Get a user's saved card from public hash.
     */
    public function getCardFromHash($publicHash, $customerId = null)
    {
        if ($publicHash) {
            $cardList = $this->getUserCards($customerId);
            foreach ($cardList as $card) {
                if ($card->getPublicHash() == $publicHash) {
                    return $card;
                }
            }
        }

        return null;
    }

    /**
     * Get a user's last saved card.
     */
    public function getLastSavedCard()
    {
        // Get the cards list
        $cardList = $this->getUserCards();
        if (!empty($cardList)) {
            // Sort the array by date
            usort(
                $cardList,
                function ($a, $b) {
                    return strtotime($a->getCreatedAt()) - strtotime($b->getCreatedAt());
                }
            );

            // Return the most recent
            return $cardList[0];
        }

        return [];
    }

    /**
     * Get a user's saved cards.
     */
    public function getUserCards($customerId = null)
    {
        // Output array
        $output = [];

        // Get the customer id (currently logged in user)
        $customerId = ($customerId) ? $customerId
        : $this->customerSession->getCustomer()->getId();

        // Find the customer cards
        if ((int) $customerId > 0) {
            $cards = $this->paymentTokenManagement->getListByCustomerId($customerId);
            foreach ($cards as $card) {
                if ($this->cardHandler->isCardActive($card)) {
                    $output[] = $card;
                }
            }
        }

        return $output;
    }

    /**
     * Render a payment token.
     */
    public function renderTokenData(PaymentTokenInterface $paymentToken)
    {
        // Get the card details
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);

        // Return the formatted token
        return sprintf(
            '%s, %s: %s, %s: %s',
            $this->cardHandler->getCardScheme($details['type']),
            __('ending'),
            $details['maskedCC'],
            __('expires'),
            $details['expirationDate']
        );
    }
}
