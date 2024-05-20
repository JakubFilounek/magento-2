define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    "use strict";

    $.widget('ecomail.tracking', {
        options: {
            appId: null,
            src: '//d70shl7vidtft.cloudfront.net/ecmtr-2.4.2.js'
        },
        _create: function () {
            if (!this.options.appId) {
                return;
            }

            this.addScript();
            window.ecotrack('newTracker', 'cf', 'd2dpiwfhf3tz0r.cloudfront.net', {
                appId: this.options.appId
            });
            window.ecotrack('setUserIdFromLocation', 'ecmid');
            window.ecotrack('trackPageView');

            customerData.get('ecomail').subscribe(this.track);
        },
        track: function (data) {
            if(data && data.email) {
                window.ecotrack('setUserId', data.email);
                window.ecotrack('trackPageView');

                customerData.reload(['ecomail'], true);
            }
        },
        addScript: function () {
            if (!window.ecotrack) {
                window.GlobalSnowplowNamespace = window.GlobalSnowplowNamespace || [];
                window.GlobalSnowplowNamespace.push('ecotrack');
                window.ecotrack = function () {
                    (window.ecotrack.q = window.ecotrack.q || []).push(arguments)
                };
                window.ecotrack.q = window.ecotrack.q || [];
                var script = document.createElement('script');
                var header = document.getElementsByTagName('head')[0];
                script.async = true;
                script.src = this.options.src;
                header.appendChild(script);
            }
        }
    });
    return $.ecomail.tracking;
});