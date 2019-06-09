<?php

/**
 * Checkout.com
 * Authorised and regulated as an electronic money institution
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

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;

class VaultMethod extends Method
{

	/**
     * @var string
     */
    const CODE = 'checkoutcom_vault';

    /**
     * @var string
     * @overriden
     */
    protected $_code = self::CODE;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * Magic Methods.
     */

    /**
     * Constructor.
     *
     * @param      \Magento\Framework\Model\Context                         $context                 The context
     * @param      \Magento\Framework\Registry                              $registry                The registry
     * @param      \Magento\Framework\Api\ExtensionAttributesFactory        $extensionFactory        The extension factory
     * @param      \Magento\Framework\Api\AttributeValueFactory             $customAttributeFactory  The custom attribute factory
     * @param      \Magento\Payment\Helper\Data                             $paymentData             The payment data
     * @param      \Magento\Framework\App\Config\ScopeConfigInterface       $scopeConfig             The scope configuration
     * @param      \Magento\Payment\Model\Method\Logger                     $logger                  The logger
     * @param      \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress     $remoteAddress           The remote address
     * @param      \CheckoutCom\Magento2\Gateway\Config\Config              $config                  The configuration
     * @param      \CheckoutCom\Magento2\Model\Service\ApiHandlerService    $apiHandler              The api handler
     * @param      \CheckoutCom\Magento2\Model\Service\QuoteHandlerService  $quoteHandler            The quote handler
     * @param      \CheckoutCom\Magento2\Model\Service\CardHandlerService   $cardHandler             The card handler
     * @param      \Magento\Framework\Model\ResourceModel\AbstractResource  $resource                The resource
     * @param      \Magento\Framework\Data\Collection\AbstractDb            $resourceCollection      The resource collection
     * @param      array                                                    $data                    The data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $remoteAddress,
            $config,
            $apiHandler,
            $quoteHandler,
            $resource,
            $resourceCollection,
            $data
        );

        $this->vaultHandler = $vaultHandler;

    }


    /**
     * Methods
     */

	/**
     * Sends a payment request.
     *
     * @param      <type>                                           $data       The data
     * @param      integer                                          $amount     The amount
     * @param      <type>                                           $currency   The currency
     * @param      string                                           $reference  The reference
     *
     * @throws     \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return     <type>                                           ( description_of_the_return_value )
     */
    public function sendPaymentRequest($data, $amount, $currency, $reference = '') {
        try {
            // Find the card token
            $card = $this->vaultHandler->getCardFromHash($data['publicHash']);

            // Set the token source
            $idSource = new IdSource($card->getGatewayToken());

            // Check CVV config
            if ($this->getValue('require_cvv', $this->_code)) {
                if (!isset($data['cvv']) || (int) $data['cvv'] == 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The CVV value is required.'));
                }
                else {
                    $idSource->cvv = $data['cvv'];
                }
            }

            // Set the payment
            $request = new Payment(
                $idSource,
                $currency
            );

            // Prepare the metadata array
            $request->metadata = ['methodId' => $this->_code];

            // Prepare the capture date setting
            $captureDate = $this->config->getCaptureTime($this->_code);

            // Prepare the MADA setting
            $madaEnabled = (bool) $this->config->getValue('mada_enabled', $this->_code);

            // Set the request parameters
            $request->capture = $this->config->needsAutoCapture($this->_code);
            $request->amount = $amount*100;
            $request->reference = $reference;
            $request->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
            $request->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
            $request->threeDs = new ThreeDs($this->config->needs3ds($this->_code));
            $request->threeDs->attempt_n3d = (bool) $this->config->getValue('attempt_n3d', $this->_code);
            $request->description = __('Payment request from %1', $this->config->getStoreName());
            // Todo - add customer to the request
            //$request->customer = $this->apiHandler->createCustomer($this->quoteHandler->getQuote());
            $request->payment_ip = $this->remoteAddress->getRemoteAddress();
            if ($captureDate) {
                $request->capture_time = $this->config->getCaptureTime();
            }

            // Mada BIN Check
            if (isset($data['cardBin']) && $this->cardHandler->isMadaBin($data['cardBin']) && $madaEnabled) {
                $request->metadata = ['udf1' => 'MADA'];
            }

            // Send the charge request
            $response = $this->apiHandler->checkoutApi
                ->payments()
                ->request($request);

            return $response;
        }

        catch(\Exception $e) {

        }
    }

    /**
     * { function_description }
     *
     * @param      \Magento\Payment\Model\InfoInterface             $payment  The payment
     *
     * @throws     \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return     self                                             ( description_of_the_return_value )
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        // Check the status
        if (!$this->canVoid()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void action is not available.'));
        }

        // Process the void request
        $response = $this->apiHandler->voidTransaction($payment);
        if (!$this->apiHandler->isValidResponse($response)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void request could not be processed.'));
        }

        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Check the status
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        // Process the refund request
        $response = $this->apiHandler->refundTransaction($payment, $amount);
        if (!$this->apiHandler->isValidResponse($response)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund request could not be processed.'));
        }

        return $this;
    }

    /**
     * Check whether method is available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code);
        }

        return false;
    }
}
