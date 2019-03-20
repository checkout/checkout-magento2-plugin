<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

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
                $list []= array('id' => $id, 'desc' => static::PAYMENT_LIST[$id]);
            }

            $value = json_encode($list);

        }

    }


}
