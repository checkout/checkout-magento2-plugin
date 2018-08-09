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

use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Gateway\Helper\SubjectReader;

class MadaRequest extends AbstractRequest {

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(
        Config $config, 
        SubjectReader $subjectReader,
        CustomerSession $customerSession
    ) {
        parent::__construct($config, $subjectReader);
        $this->customerSession = $customerSession;
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {
        // Prepare the output
        $arr = ['udf1' => ''];

        // Add a flag for the MADA charge
        $isMadaBin = $this->customerSession->getData('checkoutSessionData')['isMadaBin'];
        if ($this->config->isMadaEnabled() && $isMadaBin) {
            $arr = ['udf1' => 'MADA'];
        }

        return $arr;
    }
}
