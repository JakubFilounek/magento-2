define([], function () {
    'use strict';

    return function (placeOrderAction) {
        return function (paymentData, messageContainer) {
            paymentData = paymentData || {};
            paymentData.extension_attributes = paymentData.extension_attributes || {};
            paymentData.extension_attributes.ecomail_newsletter_opt_out =
                window.localStorage.getItem('ecomail_newsletter_opt_out') === '1';

            return placeOrderAction(paymentData, messageContainer);
        };
    };
});
