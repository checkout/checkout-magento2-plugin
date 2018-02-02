<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Gateway\Helper\SubjectReader;

abstract class AbstractRequest implements BuilderInterface {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * AbstractRequest constructor.
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(Config $config, SubjectReader $subjectReader) {
        $this->config           = $config;
        $this->subjectReader    = $subjectReader;
    }

}
