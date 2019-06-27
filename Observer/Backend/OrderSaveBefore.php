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

/**
 * Class OrderSaveBefore.
 */
class OrderSaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    protected $backendAuthSession;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * @var TransactionHandlerService
     */
    protected $transactionHandler;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Array
     */
    protected $params;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
        $this->messageManager = $messageManager;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->vaultHandler = $vaultHandler;
        $this->transactionHandler = $transactionHandler;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;

        // Get the request parameters
        $this->params = $this->request->getParams();
    }

    /**
     * OrderSaveBefore constructor.
     */
    public function execute(Observer $observer)
    {
        try {
            // Get the order
            $this->order = $observer->getEvent()->getOrder();

            // Get the method id
            $this->methodId = $this->order->getPayment()->getMethodInstance()->getCode();

            // Process the payment
            if ($this->needsMotoProcessing()) {
                // Set the source
                $source = $this->getSource();

                // Set the payment
                $request = new Payment(
                    $source,
                    $this->order->getOrderCurrencyCode()
                );

                // Prepare the metadata array
                $request->metadata = ['methodId' => $this->methodId];

                // Prepare the capture date setting
                $captureDate = $this->config->getCaptureTime($this->methodId);

                // Set the request parameters
                $request->capture = $this->config->needsAutoCapture($this->methodId);
                $request->amount = $this->order->getGrandTotal()*100;
                $request->reference = $this->order->getIncrementId();
                $request->payment_ip = $this->remoteAddress->getRemoteAddress();
                $request->payment_type = 'MOTO';
                // Todo - add customer to the request
                //$request->customer = $this->apiHandler->createCustomer($this->order);
                if ($captureDate) {
                    $request->capture_on = $this->config->getCaptureTime();
                }

                // Send the charge request
                $response = $this->apiHandler->checkoutApi
                    ->payments()
                    ->request($request);

                // Logging
                $this->logger->display($response);

                // Add the response to the order
                if ($this->apiHandler->isValidResponse($response)) {
                    $this->utilities->setPaymentData($this->order, $response);
                    $this->messageManager->addSuccessMessage(
                        __('The payment request was successfully processed.')
                    );
                } else {
                    $this->messageManager->addErrorMessage(
                        __('The transaction could not be processed. Please check the payment details.')
                    );
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The gateway declined a MOTO payment request.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $this;
        }
    }

    /**
     * Checks if the MOTO logic should be triggered.
     */
    protected function needsMotoProcessing()
    {
        try {
            return $this->backendAuthSession->isLoggedIn()
            && isset($this->params['ckoCardToken'])
            && $this->methodId == 'checkoutcom_moto'
            && !$this->transactionHandler->hasTransaction(
                Transaction::TYPE_AUTH,
                $this->order
            );
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Provide a source from request.
     */
    protected function getSource()
    {
        try {
            if ($this->isCardToken()) {
                return new TokenSource($this->params['ckoCardToken']);
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Checks if a card token is available.
     */
    protected function isCardToken()
    {
        return isset($this->params['ckoCardToken'])
        && !empty($this->params['ckoCardToken']);
    }

    /**
     * Checks if a public hash is available.
     */
    protected function isSavedCard()
    {
        return isset($this->params['publicHash'])
        && !empty($this->params['publicHash'])
        && isset($this->params['cvv'])
        && !empty($this->params['cvv']);
    }
}
