<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\AlipaySource;
use \Checkout\Models\Payments\BoletoSource;
use \Checkout\Models\Payments\GiropaySource;
use \Checkout\Models\Payments\IdealSource;
use \Checkout\Models\Payments\PoliSource;
use \Checkout\Models\Payments\SofortSource;

class AlternativePaymentMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_alternative_payments';

    /**
     * @var string
     */
    const FIELD_ALTERNATIVES = 'alternatives';

    /**
     * @var string
     */
    const FIELD_ACTIVE = 'active';

    /**
     * @var string
     */
    const FIELD_TITLE = 'title';

    /**
     * @var array
     */
    const FIELDS = array(AlternativePaymentMethod::FIELD_TITLE,
                        AlternativePaymentMethod::FIELD_ACTIVE,
                        AlternativePaymentMethod::FIELD_ALTERNATIVES);

    /**
     * @var string
     */
    const PAYMENT_SEPA = 'sepa';

    /**
     * @var string
     */
    const PAYMENT_ALIPAY = 'alipay';

    /**
     * @var string
     */
    const PAYMENT_BOLETO = 'boleto';

    /**
     * @var string
     */
    const PAYMENT_GIROPAY = 'giropay';

    /**
     * @var string
     */
    const PAYMENT_IDEAL = 'ideal';

    /**
     * @var string
     */
    const PAYMENT_POLI = 'poli';

    /**
     * @var string
     */
    const PAYMENT_QIWI = 'qiwi';

    /**
     * @var string
     */
    const PAYMENT_SAFETYPAY = 'safetypay';

    /**
     * @var string
     */
    const PAYMENT_KLARNA = 'klarna';

    /**
     * @var string
     */
    const PAYMENT_SOFORT = 'sofort';

    /**
     * @var array
     */
    const PAYMENT_LIST = array(
        AlternativePaymentMethod::PAYMENT_SEPA => 'SEPA',
        AlternativePaymentMethod::PAYMENT_ALIPAY => 'Alipay',
        AlternativePaymentMethod::PAYMENT_BOLETO => 'Boleto',
        AlternativePaymentMethod::PAYMENT_GIROPAY => 'Giropay',
        AlternativePaymentMethod::PAYMENT_IDEAL => 'iDEAL',
        AlternativePaymentMethod::PAYMENT_POLI => 'Poli',
        //AlternativePaymentMethod::PAYMENT_QIWI => 'Qiwi',
        //AlternativePaymentMethod::PAYMENT_SAFETYPAY => 'SafetyPay',
        //AlternativePaymentMethod::PAYMENT_KLARNA => 'Klarna',
        AlternativePaymentMethod::PAYMENT_SOFORT => 'Sofort'
    );

    /**
     * @var array
     */
    const PAYMENT_FIELDS = array(
        AlternativePaymentMethod::PAYMENT_SEPA => AlternativePaymentMethod::PAYMENT_FIELDS_SEPA,
        AlternativePaymentMethod::PAYMENT_ALIPAY => AlternativePaymentMethod::PAYMENT_FIELDS_ALIPAY,
        AlternativePaymentMethod::PAYMENT_BOLETO => AlternativePaymentMethod::PAYMENT_FIELDS_BOLETO,
        AlternativePaymentMethod::PAYMENT_GIROPAY => AlternativePaymentMethod::PAYMENT_FIELDS_GIROPAY,
        AlternativePaymentMethod::PAYMENT_IDEAL => AlternativePaymentMethod::PAYMENT_FIELDS_IDEAL,
        AlternativePaymentMethod::PAYMENT_POLI => AlternativePaymentMethod::PAYMENT_FIELDS_POLI,
        //AlternativePaymentMethod::PAYMENT_QIWI => AlternativePaymentMethod::PAYMENT_FIELDS_QIWI,
        //AlternativePaymentMethod::PAYMENT_SAFETYPAY => AlternativePaymentMethod::PAYMENT_FIELDS_SAFETYPAY,
        //AlternativePaymentMethod::PAYMENT_KLARNA => AlternativePaymentMethod::PAYMENT_FIELDS_KLARNA,
        AlternativePaymentMethod::PAYMENT_SOFORT => AlternativePaymentMethod::PAYMENT_FIELDS_SOFORT
    );

    /**
     * Required fields.
     */

    /**
     * @var array
     */
    const PAYMENT_FIELDS_SEPA = array('first_name', 'last_name', 'account_iban', 'billing_descriptor', 'mandate_type');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_ALIPAY = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_BOLETO = array('customerName', 'birthDate', 'cpf');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_GIROPAY = array('purpose', 'bic', 'iban');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_IDEAL = array('bic', 'description');

    /**
     * @var array
     */
    const PAYMENT_FIELDS_POLI = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_QIWI = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_SAFETYPAY = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_KLARNA = array();

    /**
     * @var array
     */
    const PAYMENT_FIELDS_SOFORT = array();

    /**
     * @var string
     * @overriden
     */
    protected $_code = AlternativePaymentMethod::CODE;

    /**
     * Modify value based on the field.
     *
     * @param      mixed  $value  The value
     * @param      string  $field  The field
     *
     * @return     mixed
     */
    public static function modifier($value, $field) {

        static::modifyAlternatives($value, $field);
        return $value;

    }

    /**
     * Modify value based on the field.
     *
     * @param      mixed  $value  The value
     * @param      string  $field  The field
     *
     * @return     mixed
     */
    protected static function modifyAlternatives(&$value, $field) {

        if($field === static::FIELD_ALTERNATIVES) {

            $list = array();
            $ids = explode(',', $value);
            foreach ($ids as $id) {
                $list []= array('id' => $id,
                                'desc' => static::PAYMENT_LIST[$id],
                                'fields' => static::PAYMENT_FIELDS[$id]);
            }

            $value = json_encode($list);

        }

    }


    /**
     * API related.
     */

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function sepa($source) {

//@todo: make sepa;

        \CheckoutCom\Magento2\Helper\Logger::write('sepa');

    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function alipay($source) {

        return new AlipaySource();

    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function boleto($source) {

        return new BoletoSource($source['customerName'],
                                $source['birthDate'],
                                $source['cpf']);

    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function giropay($source) {

        $source = new GiropaySource($source['purpose'],
                                    $source['bic']);

        $source->iban = static::getValue('iban', $array);
        //$source->info_fields = static::getValue('info_fields', $array); //todo: is this necessary

        return $source;

    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function ideal($source) {

        $source = new IdealSource($source['bic'],
                                  $source['description']);

        $source->language = static::getValue('language', $array);

        return $source;

    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function poli($source) {

        return new PoliSource();

    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function sofort($source) {

        return new PoliSource();

    }

}
