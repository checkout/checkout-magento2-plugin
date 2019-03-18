<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

class CardPaymentMethod extends Method
{

	/**
     * @var string
     */
    const CODE = 'checkoutcom_card_payment';

    /**
     * @var array
     */
    const FIELDS = array('title', 'environment', 'public_key', 'type', 'action', '3ds_enabled', 'attempt_non3ds', 'save_cards_enabled', 'save_cards_title', 'dynamic_decriptor_enabled', 'decriptor_name', 'decriptor_city', 'cvv_optional', 'mada_bin_check', 'active');

    /**
     * @var string
     * @overriden
     */
    protected $_code = CardPaymentMethod::CODE;

}
