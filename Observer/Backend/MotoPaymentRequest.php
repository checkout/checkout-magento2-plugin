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

namespace CheckoutCom\Magento2\Observer\Backend;

use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Models\Payments\BillingDescriptor;
use Checkout\Models\Payments\IdSource;
use Checkout\Models\Payments\Payment;
use Checkout\Models\Payments\Risk;
use Checkout\Models\Payments\ThreeDs;
use Checkout\Models\Payments\TokenSource;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class MotoPaymentRequest.
 */
class MotoPaymentRequest implements ObserverInterface
{
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    public $backendAuthSession;
    /**
     * $request field
     *
     * @var RequestInterface $request
     */
    public $request;
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    public $messageManager;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    public $apiHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    public $orderHandler;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    public $vaultHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    public $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    public $logger;
    /**
     * $params field
     *
     * @var array $params
     */
    public $params;
    /**
     * $order field
     *
     * @var Order $order
     */
    public $order;
    /**
     * $methodId field
     *
     * @var String $methodId
     */
    public $methodId;

    /**
     * MotoPaymentRequest constructor
     *
     * @param Session             $backendAuthSession
     * @param RequestInterface    $request
     * @param ManagerInterface    $messageManager
     * @param ApiHandlerService   $apiHandler
     * @param OrderHandlerService $orderHandler
     * @param VaultHandlerService $vaultHandler
     * @param Config               $config
     * @param Utilities           $utilities
     * @param Logger              $logger
     */
    public function __construct(
        Session $backendAuthSession,
        RequestInterface $request,
        ManagerInterface $messageManager,
        ApiHandlerService $apiHandler,
        OrderHandlerService $orderHandler,
        VaultHandlerService $vaultHandler,
        Config $config,
        Utilities $utilities,
        Logger $logger
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request            = $request;
        $this->messageManager     = $messageManager;
        $this->apiHandler         = $apiHandler;
        $this->orderHandler       = $orderHandler;
        $this->vaultHandler       = $vaultHandler;
        $this->config              = $config;
        $this->utilities          = $utilities;
        $this->logger             = $logger;
    }

    /**
     * Run the observer
     *
     * @param Observer $observer
     *
     * @return MotoPaymentRequest
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
                $source, $this->order->getOrderCurrencyCode()
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
            $request->capture      = $this->config->needsAutoCapture($this->methodId);
            $request->amount       = $this->prepareMotoAmount();
            $request->reference    = $this->order->getIncrementId();
            $request->payment_type = 'MOTO';
            if ($this->order->getIsNotVirtual()) {
                $request->shipping = $api->createShippingAddress($this->order);
            }
            $request->threeDs = new ThreeDs(false);
            $request->risk    = new Risk($this->config->needsRiskRules($this->methodId));
            $request->setIdempotencyKey(bin2hex(random_bytes(16)));

            // Billing descriptor
            if ($this->config->needsDynamicDescriptor()) {
                $request->billing_descriptor = new BillingDescriptor(
                    $this->config->getValue('descriptor_name'), $this->config->getValue('descriptor_city')
                );
            }

            // Send the charge request
            try {
                $response = $api->checkoutApi->payments()->request($request);
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
     * Checks if the MOTO logic should be triggered
     *
     * @return bool
     */
    public function needsMotoProcessing()
    {
        return $this->backendAuthSession->isLoggedIn(
            ) && isset($this->params['ckoCardToken']) && $this->methodId == 'checkoutcom_moto';
    }

    /**
     * Prepare the payment amount for the MOTO payment request
     *
     * @return float|int|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
     * Provide a source from request
     *
     * @return IdSource|TokenSource
     * @throws LocalizedException
     */
    public function getSource()
    {
        if ($this->isCardToken()) {
            // Initialize the API handler
            $api = $this->apiHandler->init();

            // Create the token source
            $tokenSource                  = new TokenSource($this->params['ckoCardToken']);
            $tokenSource->billing_address = $api->createBillingAddress($this->order);

            return $tokenSource;
        } elseif ($this->isSavedCard()) {
            $card          = $this->vaultHandler->getCardFromHash(
                $this->params['publicHash'],
                $this->order->getCustomerId()
            );
            $idSource      = new IdSource($card->getGatewayToken());
            $idSource->cvv = $this->params['cvv'];

            return $idSource;
        } else {
            $this->messageManager->addErrorMessage(
                __('Please provide the required card information for the MOTO payment.')
            );
            throw new LocalizedException(
                __('Missing required card information for the MOTO payment.')
            );
        }

        return null;
    }

    /**
     * Checks if a card token is available
     *
     * @return bool
     */
    public function isCardToken()
    {
        return isset($this->params['ckoCardToken']) && !empty($this->params['ckoCardToken']);
    }

    /**
     * Checks if a public hash is available
     *
     * @return bool
     */
    public function isSavedCard()
    {
        return isset($this->params['publicHash']) && !empty($this->params['publicHash']) && isset($this->params['cvv']) && !empty($this->params['cvv']);
    }
}
