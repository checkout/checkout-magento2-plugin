<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Plugin;

/**
 * Class CsrfValidatorSkip.
 */
class CsrfValidatorSkip
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure                                     $proceed
     * @param \Magento\Framework\App\RequestInterface      $request
     * @param \Magento\Framework\App\ActionInterface       $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        // Skip CSRF check
        if ($request->getModuleName() == 'checkout_com') {
            return;
        }

        // Proceed Magento 2 core functionalities
        $proceed($request, $action);
    }
}
