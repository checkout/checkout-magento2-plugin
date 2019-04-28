<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Account;

class AddCard extends \Magento\Framework\App\Action\Action {

    public function __construct(
        Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Force login
        $this->tools->checkLoggedIn();

        // Display the page
        $this->_view->loadLayout(); 
        $this->_view->renderLayout(); 
    } 
}