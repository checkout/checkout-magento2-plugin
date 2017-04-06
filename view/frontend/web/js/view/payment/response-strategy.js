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
