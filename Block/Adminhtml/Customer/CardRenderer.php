<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Block\Adminhtml\Customer;

use Magento\Payment\Model\CcConfigProvider;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;use Magento\Vault\Api\Data\PaymentTokenInterface;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CardRenderer extends Template {

    /**
     * @var CcConfigProvider
     */
    private $iconsProvider;
    
    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * CardRenderer constructor.
     * @param CcConfigProvider $iconsProvider
     * @param GatewayConfig $gatewayConfig
     * @param array $data
     */
    public function __construct(Context $context, CcConfigProvider $iconsProvider, GatewayConfig $gatewayConfig, PaymentTokenManagementInterface $paymentTokenManagement, CustomerRepositoryInterface $customerRepository, array $data) {
        parent::__construct($context);
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->gatewayConfig = $gatewayConfig;
        $this->iconsProvider = $iconsProvider;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Returns a list of all user cards.
     *
     * @return array
     */
    public function getStoredCards() {
        // TODO get from model or user select
        $customerId = 2;

        return $this->paymentTokenManagement->getListByCustomerId($customerId);
    }

    /**
     * Returns an icon object for a card type.
     *
     * @return array
     */
    public function getCardIcon($type) {
        return $this->iconsProvider->getIcons()[$type];
    }  

    /**
     * Returns a customer object.
     *
     * @return object
     */
    public function getCustomer($id) {
        return $this->customerRepository->getById($id);
    }  

    /**
     * Determines if can render the given token.
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token) {
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
    }

}
