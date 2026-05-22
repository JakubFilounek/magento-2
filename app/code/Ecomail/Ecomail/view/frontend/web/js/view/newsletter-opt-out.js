define([
    'uiComponent',
    'ko'
], function (Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ecomail_Ecomail/newsletter-opt-out',
            label: 'Do not subscribe me to the newsletter'
        },

        initialize: function () {
            this._super();
            this.checked = ko.observable(window.localStorage.getItem('ecomail_newsletter_opt_out') === '1');

            this.checked.subscribe(function (value) {
                window.localStorage.setItem('ecomail_newsletter_opt_out', value ? '1' : '0');
            });

            return this;
        }
    });
});
