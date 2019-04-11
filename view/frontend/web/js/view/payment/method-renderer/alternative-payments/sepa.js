define([
        'jquery'
    ],
    function ($) {
        'use strict';

        $form.append(Utilities.createInput({
            icon: 'ckojs-card',
            placeholder: 'bic',
            type: 'text',
            name: 'bic',
            required: this.fields.includes('bic'),
         }));


    $form.append(Utilities.createInput({
            icon: 'ckojs-card',
            placeholder: 'account iban',
            type: 'text',
            name: 'account_iban',
            required: this.fields.includes('account_iban'),
         }));

    }
);
