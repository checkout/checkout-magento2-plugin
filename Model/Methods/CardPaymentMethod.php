<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

class CardPaymentMethod extends Method
{

	/**
     * @var string
     */
    const CODE = 'checkoutcom_magento2_redirect_method';

    /**
     * @var array
     */
    const FIELDS = array('title', 'enabled', 'environment', 'secret_key', 'public_key', 'shared_key', 'card_payments_type', 'card_payments_action', 'card_payments_3ds_enabled', 'card_payments_attempt_non3ds', 'card_payments_save_cards_enabled', 'card_payments_save_cards_title', 'card_payments_dynamic_decriptor_enabled', 'card_payments_decriptor_name', 'card_payments_decriptor_city', 'card_payments_cvv_optional', 'card_payments_mada_bin_check');

    /**
     * @var string
     * @overriden
     */
    protected $_code = CardPaymentMethod::CODE;

}
