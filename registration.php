<?php

use Magento\Framework\Component\ComponentRegistrar;

$registrar = new ComponentRegistrar();

if ($registrar->getPath(ComponentRegistrar::MODULE, 'CheckoutCom_Magento2') === null) {
    ComponentRegistrar::register(ComponentRegistrar::MODULE, 'CheckoutCom_Magento2', __DIR__);
}







// $vendorDir = require BP . '/app/etc/vendor_path.php';
// $vendorAutoload = BP . "/{$vendorDir}/autoload.php";
/** @var \Composer\Autoload\ClassLoader $composerAutoloader */
// $composerAutoloader = include $vendorAutoload;
// $composerAutoloader->addPsr4('Ekomi\\', array(__DIR__ . '/Ekomi'));