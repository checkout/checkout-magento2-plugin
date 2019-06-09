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

use Magento\Framework\DataObject;
use Magento\Framework\Module\Dir;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;

abstract class Method extends \Magento\Payment\Model\Method\AbstractMethod
{

	protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCancel = true;
    protected $_canCapturePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

	/**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

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
            $resource,
            $resourceCollection,
            $data
        );

        $this->remoteAddress      = $remoteAddress;
        $this->config             = $config;
        $this->apiHandler         = $apiHandler;
        $this->quoteHandler       = $quoteHandler;

    }

    /**
     * Methods
     */

    /**
     * Safely get value from a multidimentional array.
     *
     * @param      array  $array  The value
     *
     * @return     Payment
     */
    public static function getValue($field, $array, $dft = null) {

        $value = null;
        $field = (array) $field;

        foreach ($field as $key) {

            if(isset($array[$key])) {
                $value = $array[$key];
                $array = $array[$key];
            } else {
                $value = $dft;
                break;
            }

        }

        return $value;

    }

    /**
     * API related.
     */

    /**
     * Create a payment object based on the body.
     *
     * @param      array  $array  The value
     *
     * @return     Payment
     */
    protected function createPayment(IdSource $source, int $amount, string $currency, string $reference, $methodId) {

        $payment = null;

        // Create payment object
        $payment = new Payment($source, $currency);

        // Prepare the metadata array
        $payment->metadata = ['methodId' => $methodId];

        // Set the payment specifications
        $payment->capture = $this->config->needsAutoCapture($this->_code);
        $payment->amount = $amount * 100;
        $payment->reference = $reference;
        $payment->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $payment->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';

        $payment->description = __('Payment request from %1', $this->config->getStoreName());
        $payment->payment_ip = $this->remoteAddress->getRemoteAddress();

        return $payment;
    }
}
