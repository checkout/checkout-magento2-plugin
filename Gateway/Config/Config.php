<?php
/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 7
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2019 Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */
 
namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Module\Dir;


class Config
{
    
    const CODE = 'checkoutcom_magento2_redirect_method';

    /**
     * Config constructor.
     */
    public function __construct() {

    }

    public function getConfig() {
        return [];
    }

}
