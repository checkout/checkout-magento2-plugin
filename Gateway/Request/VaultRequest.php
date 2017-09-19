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
use Magento\Customer\Model\Session as CustomerSession;

class VaultRequest implements BuilderInterface {

    /**
     * Additional options in request to gateway
     */
    const OPTIONS = 'options';

    /**
     * The option that determines whether the payment method associated with
     * the successful transaction should be stored in the Vault.
     */
    const STORE_IN_VAULT_ON_SUCCESS = 'storeInVaultOnSuccess';

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * VaultRequest constructor.
     * @param CustomerSession $customerSession
     */
    public function __construct(CustomerSession $customerSession) {
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        return [
            'udf2' => ($this->getVaultSaveInfo()) ? self::STORE_IN_VAULT_ON_SUCCESS : ''
        ];
    }

    public function getVaultSaveInfo() {

        // Get the checkout session data
        $checkoutSessionData = $this->customerSession->getData('checkoutSessionData');

        // Check if save card is requested
        if (isset($checkoutSessionData['saveShopperCard'])) {
            return filter_var($checkoutSessionData['saveShopperCard'], FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }
}
