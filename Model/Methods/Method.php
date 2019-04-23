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
    public function __construct(...$args) {
        parent::__construct(...$args);
    }
}
