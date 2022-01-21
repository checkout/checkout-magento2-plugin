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

declare(strict_types=1);

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
use Magento\Sales\Model\Order;

/**
 * Class MotoPaymentRequest
 */
class MotoPaymentRequest implements ObserverInterface
{
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    private $backendAuthSession;
    /**
     * $request field
     *
     * @var RequestInterface $request
     */
    private $request;
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    private $messageManager;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    private $vaultHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;

    /**
     * MotoPaymentRequest constructor
     *
     * @param Session             $backendAuthSession
     * @param RequestInterface    $request
     * @param ManagerInterface    $messageManager
     * @param ApiHandlerService   $apiHandler
     * @param OrderHandlerService $orderHandler
     * @param VaultHandlerService $vaultHandler
     * @param Config              $config
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
        $this->config             = $config;
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
    public function execute(Observer $observer): MotoPaymentRequest
    {
        // Get the request parameters
        /** @var mixed[] $params */
        $params = $this->request->getParams();

        // Get the order
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        // Get the method id
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // Get the store code
        $storeCode = $order->getStore()->getCode();

        // Process the payment
        if ($this->needsMotoProcessing($methodId, $params)) {
            // Prepare the response container
            $response = null;

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Set the source
            $source = $this->getSource($order, $params);

            // Set the payment
            $request = new Payment(
                $source, $order->getOrderCurrencyCode()
            );

            // Prepare the metadata array
            $request->metadata = array_merge(
                ['methodId' => $methodId],
                $this->apiHandler->getBaseMetadata()
            );

            // Prepare the capture setting
            $needsAutoCapture = $this->config->needsAutoCapture($methodId);
            $request->capture = $needsAutoCapture;
            if ($needsAutoCapture) {
                $request->capture_on = $this->config->getCaptureTime($methodId);
            }

            // Set the request parameters
            $request->capture      = $this->config->needsAutoCapture($methodId);
            $request->amount       = $this->prepareMotoAmount($order);
            $request->reference    = $order->getIncrementId();
            $request->payment_type = 'MOTO';
            if ($order->getIsNotVirtual()) {
                $request->shipping = $api->createShippingAddress($order);
            }
            $request->threeDs = new ThreeDs(false);
            $request->risk    = new Risk($this->config->needsRiskRules($methodId));
            $request->setIdempotencyKey(bin2hex(random_bytes(16)));

            // Billing descriptor
            if ($this->config->needsDynamicDescriptor()) {
                $request->billing_descriptor = new BillingDescriptor(
                    $this->config->getValue('descriptor_name'), $this->config->getValue('descriptor_city')
                );
            }

            // Send the charge request
            try {
                $response = $api->getCheckoutApi()->payments()->request($request);
            } catch (CheckoutHttpException $e) {
                $this->logger->write($e->getBody());
            } finally {
                // Logging
                $this->logger->display($response);

                // Add the response to the order
                if ($api->isValidResponse($response)) {
                    $this->utilities->setPaymentData($order, $response);
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
     * @param string  $methodId
     * @param mixed[] $params
     *
     * @return bool
     */
    protected function needsMotoProcessing(string $methodId, array $params): bool
    {
        return $this->backendAuthSession->isLoggedIn(
            ) && isset($params['ckoCardToken']) && $methodId === 'checkoutcom_moto';
    }

    /**
     * Prepare the payment amount for the MOTO payment request
     *
     * @param Order $order
     *
     * @return float|int|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function prepareMotoAmount(Order $order)
    {
        // Get the payment instance
        $amount = $order->getGrandTotal();

        // Return the formatted amount
        return $this->orderHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $order
        );
    }

    /**
     * Provide a source from request
     *
     * @param Order $order
     * @param mixed[] $params
     *
     * @return IdSource|TokenSource
     * @throws LocalizedException
     */
    protected function getSource(Order $order, array $params)
    {
        if ($this->isCardToken($params)) {
            // Initialize the API handler
            $api = $this->apiHandler->init();

            // Create the token source
            $tokenSource                  = new TokenSource($params['ckoCardToken']);
            $tokenSource->billing_address = $api->createBillingAddress($order);

            return $tokenSource;
        } elseif ($this->isSavedCard($params)) {
            $card          = $this->vaultHandler->getCardFromHash(
                $params['publicHash'],
                $order->getCustomerId()
            );
            $idSource      = new IdSource($card->getGatewayToken());
            $idSource->cvv = $params['cvv'];

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
     * @param mixed[] $params
     *
     * @return bool
     */
    protected function isCardToken(array $params): bool
    {
        return isset($params['ckoCardToken']) && !empty($params['ckoCardToken']);
    }

    /**
     * Checks if a public hash is available
     *
     * @param mixed[] $params
     *
     * @return bool
     */
    protected function isSavedCard(array $params): bool
    {
        return isset($params['publicHash'], $params['cvv']) && !empty($params['publicHash']) && !empty($params['cvv']);
    }
}
