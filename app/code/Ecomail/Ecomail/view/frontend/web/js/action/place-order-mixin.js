define([], function () {
    'use strict';

    function getStoredOptOut() {
        try {
            return window.localStorage.getItem('ecomail_newsletter_opt_out') === '1';
        } catch (e) {
            return false;
        }
    }

    return function (placeOrderAction) {
        return function (paymentData, messageContainer) {
            paymentData = paymentData || {};
            paymentData.extension_attributes = paymentData.extension_attributes || {};
            paymentData.extension_attributes.ecomail_newsletter_opt_out = getStoredOptOut();

            return placeOrderAction(paymentData, messageContainer);
        };
    };
});
