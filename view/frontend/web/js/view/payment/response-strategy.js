/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
define([
    'jquery'
], function ($) {
    'use strict';

    return function(response) {
        var
            deferred = $.Deferred(),
            html = $.parseHTML(response),
            formId = 'cko-form-redirection';

        if(html[1] !== undefined) {
            var $form = $(html[1]);

            if($form.length && $form.attr('name') === 'ckoform') {
                $form.attr('id', formId).attr('styles', 'display:none');

                $('body').append( $form );
                $('#' + formId).submit();

                return deferred.reject();
            }
        }

        return deferred.resolve();
    }

});
