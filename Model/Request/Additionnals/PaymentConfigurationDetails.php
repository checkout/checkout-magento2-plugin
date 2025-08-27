<?php

namespace CheckoutCom\Magento2\Model\Request\Additionnals;

use Checkout\Payments\Sessions\Card;

class PaymentConfigurationDetails extends Card {

     /**
     * @var AccountHolder
     */
    public $account_holder;
}