define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (config, element) {
        var $warning = $(element);
        var $form = $warning.closest('form');
        var savedState;

        function addApiKeyToggle() {
            var $field = $('#ecomail_general_api_key');
            var $button;

            if (!$field.length || $field.data('ecomailApiKeyToggleReady')) {
                return;
            }

            $field.data('ecomailApiKeyToggleReady', true);
            $field.addClass('ecomail-api-key-input');
            $field.wrap('<span class="ecomail-api-key-control"></span>');

            $button = $('<button/>', {
                type: 'button',
                class: 'action-default ecomail-api-key-toggle',
                title: $t('Show API key'),
                'aria-label': $t('Show API key')
            }).html('<span aria-hidden="true">&#128065;</span>');

            $field.after($button);

            $button.on('click', function () {
                var isHidden = $field.attr('type') !== 'text';
                var label = isHidden ? $t('Hide API key') : $t('Show API key');

                $field.attr('type', isHidden ? 'text' : 'password');
                $button.attr({
                    title: label,
                    'aria-label': label
                });
                $field.trigger('focus');
            });
        }

        if (!$form.length) {
            $form = $('#config-edit-form');
        }

        if (!$form.length) {
            return;
        }

        addApiKeyToggle();

        function getState() {
            return $form.serialize();
        }

        function updateWarning() {
            var isChanged = savedState !== null && getState() !== savedState;

            $warning.toggleClass('is-visible', isChanged);
        }

        savedState = null;

        window.setTimeout(function () {
            savedState = getState();
            updateWarning();
        }, 0);

        $form.on('input change', 'input, select, textarea', updateWarning);

        $warning.find('.ecomail-unsaved-save').on('click', function () {
            var $saveButton = $('#save');

            if ($saveButton.length) {
                $saveButton.trigger('click');
                return;
            }

            $form.trigger('submit');
        });

        $form.on('submit', function () {
            $warning.removeClass('is-visible');
        });
    };
});
