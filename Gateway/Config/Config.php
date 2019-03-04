<?php

namespace CheckoutCom\Magento2\Gateway\Config;


use Magento\Checkout\Model\Session;

class Config extends \Magento\Payment\Gateway\Config\Config
{

    /**
     * @var string
     */
    const CODE_CARD = 'checkoutcom_magento2_redirect_method';

    /**
     * @var string
     */
    const CODE_ALTERNATIVE = 'checkoutcom_alternative_payments';

    /**
     * @var string
     */
    const CODE_GOOGLE = 'checkoutcom_google_pay';

    /**
     * @var string
     */
    const CODE_APPLE = 'checkoutcom_apple_pay';

    /**
     * @var array
     */
    const PAYMENT_METHODS = array(Config::CODE_CARD, Config::CODE_ALTERNATIVE, Config::CODE_GOOGLE, Config::CODE_APPLE);



    public function getConfig($sessionID) {


        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        $this->setMethodCode(Config::CODE_CARD);
        \CheckoutCom\Magento2\Helper\Logger::write($this->getValue('button_label'));



        // foreach(static::PAYMENT_METHODS as $method) {





        // }

        // return $this->getConfigAquila();
        return [];

    }




    public function getConfigAquila()
    {


        //@todo: get values from the config

        //\CheckoutCom\Magento2\Helper\Logger::write(print_r($this,1));

        return [
            'payment' => [
                static::CODE_CARD => ['store' => 'asd'],
                static::CODE_ALTERNATIVE => ['store' => 'asd'],
                static::CODE_GOOGLE => ['store' => 'asd'],
                static::CODE_APPLE => ['store' => 'asd'],
            ]
        ];

    }








    /**
     * Retrieve active system payments
     *
     * @return array
     * @api
     */
    public function getEnabledMethods()
    {

        $methods = [];
        foreach (static::PAYMENT_METHODS as $method) {

            $this->setMethodCode($method);
            $enabled = $this->getValue($method . '_enabled');
            if($enabled) {
                $methods []= $method;
            }

        }









        $methods = [];
        foreach ($this->getValue('payment', ScopeInterface::SCOPE_STORE, null) as $code => $data) {
            if (isset($data['active'], $data['model']) && (bool)$data['active']) {
                /** @var MethodInterface $methodModel Actually it's wrong interface */
                $methodModel = $this->_paymentMethodFactory->create($data['model']);
                $methodModel->setStore(null);
                if ($methodModel->getConfigData('active', null)) {
                    $methods[$code] = $methodModel;
                }
            }
        }
        return $methods;
    }




}
