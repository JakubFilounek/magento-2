define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    var loaded = false;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }

        return '';
    }

    function loadScript(src, beforeInsert) {
        var script = document.createElement('script');
        var firstScript = document.getElementsByTagName('script')[0];

        script.async = true;
        script.src = src;

        if (typeof beforeInsert === 'function') {
            beforeInsert(script);
        }

        if (firstScript && firstScript.parentNode) {
            firstScript.parentNode.insertBefore(script, firstScript);
            return;
        }

        document.head.appendChild(script);
    }

    function initTracker(config) {
        if (!window.ecotrack) {
            window.GlobalSnowplowNamespace = window.GlobalSnowplowNamespace || [];
            window.GlobalSnowplowNamespace.push('ecotrack');
            window.ecotrack = function () {
                (window.ecotrack.q = window.ecotrack.q || []).push(arguments);
            };
            window.ecotrack.q = window.ecotrack.q || [];

            loadScript('https://d70shl7vidtft.cloudfront.net/ecmtr-2.4.2.js');
        }

        window.ecotrack('newTracker', 'cf', 'd2dpiwfhf3tz0r.cloudfront.net', {
            appId: config.appId
        });
        window.ecotrack('setUserIdFromLocation', 'ecmid');
        window.ecotrack('trackPageView');

        if (config.trackProductView && config.productCode) {
            window.ecotrack('trackStructEvent', 'ECM_PRODUCT_VIEW', config.productCode);
        }

        customerData.get('ecomail').subscribe(function (items) {
            if (items && items.email) {
                window.ecotrack('setUserId', items.email);
                window.ecotrack('trackPageView');
                customerData.reload(['ecomail'], true);
            }
        });
    }

    function initWidget(config) {
        if (window.ecmwidget) {
            return;
        }

        window['ecm-widget'] = 'ecmwidget';
        window.ecmwidget = function () {
            (window.ecmwidget.q = window.ecmwidget.q || []).push(arguments);
        };

        loadScript('https://d70shl7vidtft.cloudfront.net/widget.js', function (script) {
            script.id = config.formId;
            script.dataset.a = config.formAccount;
        });
    }

    return function (config) {
        function hasCookieConsent() {
            return !config.waitForCookieConsent || getCookie('user_allowed_save_cookie') === '1';
        }

        function loadEcomailScripts() {
            if (loaded || !hasCookieConsent()) {
                return;
            }

            loaded = true;

            if (config.trackingEnabled && config.appId) {
                initTracker(config);
            }

            if (config.formEnabled && config.formId && config.formAccount) {
                initWidget(config);
            }
        }

        if (hasCookieConsent()) {
            loadEcomailScripts();

            return;
        }

        var consentCheck = window.setInterval(function () {
            if (hasCookieConsent()) {
                window.clearInterval(consentCheck);
                loadEcomailScripts();
            }
        }, 1000);
    };
});
