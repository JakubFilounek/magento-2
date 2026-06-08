define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $warning = $(element);
        var $form = $warning.closest('form');
        var savedState;

        if (!$form.length) {
            $form = $('#config-edit-form');
        }

        if (!$form.length) {
            return;
        }

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
