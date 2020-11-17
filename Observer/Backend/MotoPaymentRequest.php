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

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Payment\Transaction;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\BillingDescriptor;
use \Checkout\Library\Exceptions\CheckoutHttpException;
use \Checkout\Models\Payments\ThreeDs;
use \Checkout\Models\Payments\Risk;

/**
 * Class MotoPaymentRequest.
 */
class MotoPaymentRequest implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Array
     */
    public $params;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var String
     */
    public $methodId;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->vaultHandler = $vaultHandler;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
    }

    /**
     * Run the observer.
     */
    public function execute(Observer $observer)
    {
        // Get the request parameters
        $this->params = $this->request->getParams();

        // Get the order
        $this->order = $observer->getEvent()->getOrder();

        // Get the method id
        $this->methodId = $this->order->getPayment()->getMethodInstance()->getCode();

        // Get the store code
        $storeCode = $this->order->getStore()->getCode();

        // Process the payment
        if ($this->needsMotoProcessing()) {
            // Prepare the response container
            $response = null;

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Set the source
            $source = $this->getSource();

            // Set the payment
            $request = new Payment(
                $source,
                $this->order->getOrderCurrencyCode()
            );

            // Prepare the metadata array
            $request->metadata = array_merge(
                ['methodId' => $this->methodId],
                $this->apiHandler->getBaseMetadata()
            );

            // Prepare the capture setting
            $needsAutoCapture = $this->config->needsAutoCapture($this->methodId);
            $request->capture = $needsAutoCapture;
            if ($needsAutoCapture) {
                $request->capture_on = $this->config->getCaptureTime($this->methodId);
            }

            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($this->methodId);
            $request->amount = $this->prepareMotoAmount();
            $request->reference = $this->order->getIncrementId();
            $request->payment_type = 'MOTO';
            $request->shipping = $api->createShippingAddress($this->order);
            $request->threeDs = new ThreeDs(false);
            $request->risk = new Risk($this->config->needsRiskRules($this->methodId));
            
            // Billing descriptor
            if ($this->config->needsDynamicDescriptor()) {
                $request->billing_descriptor = new BillingDescriptor(
                    $this->config->getValue('descriptor_name'),
                    $this->config->getValue('descriptor_city')
                );
            }

            // Send the charge request
            try {
                $response = $api->checkoutApi
                    ->payments()->request($request);
            } catch (CheckoutHttpException $e) {
                $this->logger->write($e->getBody());
            } finally {
                // Logging
                $this->logger->display($response);

                // Add the response to the order
                if ($api->isValidResponse($response)) {
                    $this->utilities->setPaymentData($this->order, $response);
                    $this->messageManager->addSuccessMessage(
                        __('The payment request was successfully processed.')
                    );
                } else {
                    $this->messageManager->addErrorMessage(
                        __('The transaction could not be processed. Please check the payment details.')
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Checks if the MOTO logic should be triggered.
     */
    public function needsMotoProcessing()
    {
        return $this->backendAuthSession->isLoggedIn()
        && isset($this->params['ckoCardToken'])
        && $this->methodId == 'checkoutcom_moto';
    }

    /**
     * Prepare the payment amount for the MOTO payment request.
     */
    public function prepareMotoAmount()
    {
        // Get the payment instance
        $amount = $this->order->getGrandTotal();
        // Return the formatted amount
        return $this->orderHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $this->order
        );
    }

    /**
     * Provide a source from request.
     */
    public function getSource()
    {
        if ($this->isCardToken()) {
            // Initialize the API handler
            $api = $this->apiHandler->init();

            // Create the token source
            $tokenSource = new TokenSource($this->params['ckoCardToken']);
            $tokenSource->billing_address = $api->createBillingAddress($this->order);

            return $tokenSource;
        } elseif ($this->isSavedCard()) {
            $card = $this->vaultHandler->getCardFromHash(
                $this->params['publicHash'],
                $this->order->getCustomerId()
            );
            $idSource = new IdSource($card->getGatewayToken());
            $idSource->cvv = $this->params['cvv'];

            return $idSource;
        } else {
            $this->messageManager->addErrorMessage(
                __('Please provide the required card information for the MOTO payment.')
            );
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Missing required card information for the MOTO payment.')
            );
        }

        return null;
    }

    /**
     * Checks if a card token is available.
     */
    public function isCardToken()
    {
        return isset($this->params['ckoCardToken'])
        && !empty($this->params['ckoCardToken']);
    }

    /**
     * Checks if a public hash is available.
     */
    public function isSavedCard()
    {
        return isset($this->params['publicHash'])
        && !empty($this->params['publicHash'])
        && isset($this->params['cvv'])
        && !empty($this->params['cvv']);
    }
}
