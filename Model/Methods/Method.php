<?php

namespace CheckoutCom\Magento2\Model\Methods;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use \Checkout\Models\Payments\Payment;
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
    protected $backendAuthSession;
    protected $cart;
    protected $urlBuilder;
    protected $_objectManager;
    protected $invoiceSender;
    protected $transactionFactory;
    protected $customerSession;
    protected $checkoutSession;
    protected $checkoutData;
    protected $quoteRepository;
    protected $quoteManagement;
    protected $orderSender;
    protected $sessionQuote;
    protected $config;
    protected $encryptor;


    /**
     * Methods
     */

    /**
     * Modify value based on the field.
     *
     * @param      mixed  $value  The value
     * @param      string  $field  The field
     *
     * @return     mixed
     */
    public static function modifier($value, $field) {

        return $value;

    }


    /**
     * API related.
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
     * Create a payment object based on the body.
     *
     * @param      array  $array  The value
     *
     * @return     Payment
     */
    public static function createPayment($array, $currency) {

        $source = $array['source'];
        $payment = null;
        if($source['type']) {

            $source = static::{$source['type']}($source);
            $payment = new Payment($source, $currency); // Currency

        }

        return $payment;

    }

}
