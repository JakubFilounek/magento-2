define([
    'uiComponent',
    'ko'
], function (Component, ko) {
    'use strict';

    function getStoredOptOut() {
        try {
            return window.localStorage.getItem('ecomail_newsletter_opt_out') === '1';
        } catch (e) {
            return false;
        }
    }

    function setStoredOptOut(value) {
        try {
            window.localStorage.setItem('ecomail_newsletter_opt_out', value ? '1' : '0');
        } catch (e) {
            // Checkout must keep working when browser storage is unavailable.
        }
    }

    return Component.extend({
        defaults: {
            template: 'Ecomail_Ecomail/newsletter-opt-out',
            label: 'Do not subscribe me to the newsletter'
        },

        initialize: function () {
            this._super();
            this.checked = ko.observable(getStoredOptOut());

            this.checked.subscribe(function (value) {
                setStoredOptOut(value);
            });

            return this;
        }
    });
});
