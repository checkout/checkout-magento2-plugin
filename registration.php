<?php

use Magento\Framework\Component\ComponentRegistrar;

$registrar = new ComponentRegistrar();

if ($registrar->getPath(ComponentRegistrar::MODULE, 'CheckoutCom_Magento2') === null) {
    ComponentRegistrar::register(ComponentRegistrar::MODULE, 'CheckoutCom_Magento2', __DIR__);
}
