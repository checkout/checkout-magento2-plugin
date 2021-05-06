define(function () {
    'use strict';

    var mixin = {
        isBaseGrandTotalDisplayNeeded: function () {
            let checkoutConfig = window.checkoutConfig.payment["checkoutcom_magento2"];
            return !parseInt(checkoutConfig["checkoutcom_configuration"]['active']);
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
