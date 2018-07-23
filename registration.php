<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

use Magento\Framework\Component\ComponentRegistrar;
use CheckoutCom\Magento2\Helper\Tools;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'CheckoutCom_Magento2', __DIR__);
