<?php

namespace CheckoutCom\Magento2\Plugin;

use Closure;
use Magento\Framework\View\Element\Html\Links;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class CustomerDashboardLinksManagement
 */
class CustomerDashboardLinksManagement
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;


    public function __construct (ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    public function aroundRenderLink(Links $subject, Closure $proceed, AbstractBlock $link)
    {
        // Get the output   
        $output = $proceed($link);

        // If it's the dashboard menu
        if ($subject->getNameInLayout() == 'customer_account_navigation') {
            // If it's the stored payment cards lenu link
            if ($link->getNameInLayout() == 'customer-account-navigation-my-credit-cards-link') {
                if ((bool) $this->scopeConfig->getValue('payment/checkout_com_cc_vault/hide_card_storage')) {
                    return '';
                }
            }
        }

        return $output;
    }
}
