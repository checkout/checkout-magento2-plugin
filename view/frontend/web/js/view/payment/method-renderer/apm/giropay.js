define([
        'jquery'
    ],
    function ($) {
        'use strict';

        $form.append(Utilities.createInput({
            icon: 'ckojs-card',
            placeholder: 'bic',
            type: 'tel',
            name: 'bic',
            required: this.fields.includes('bic'),
         }));


        $form.append(Utilities.createInput({
            icon: 'ckojs-card',
            placeholder: 'purpose',
            type: 'text',
            name: 'purpose',
            required: this.fields.includes('purpose')
         }));

    }
);
