<?php

namespace CheckoutCom\Magento2\Model\Adminhtml\Source;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Payment\Model\Config as BaseConfig;

class CcType extends \Magento\Payment\Model\Source\Cctype
{
 
    /**
     * @var Config 
     */
    protected $config;
    
    /**
     * CcType constructor.
     * @param Config $config
     * @param BaseConfig $baseConfig
     */
    public function __construct(Config $config, BaseConfig $baseConfig){
        parent::__construct($baseConfig);
        $this->config = $config;
    }
    
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $checkoutComCc = $this->config->getCcTypesMapper();
        $cardsToReturn = [];
        
        foreach ($this->_paymentConfig->getCcTypes() as $code => $name) {
            if (in_array($code, $checkoutComCc)) {
                $cardsToReturn[] = ['value' => $code, 'label' => $name];
            }
        }
        
        return $cardsToReturn;
    }
}