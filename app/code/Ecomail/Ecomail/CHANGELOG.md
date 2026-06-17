# Ecomail changelog

## v2.3.0 - 11 June, 2026
* Initial sync of existing customers and orders now runs as a Magento cron-backed background job instead of blocking the request.
* Added an admin panel to start the initial sync and follow its progress, with a summary of the last completed run.
* Prevented overlapping initial syncs so a new run cannot start while one is pending or running.
* Added a Recent API Requests log in admin showing the status of recent Ecomail calls (no payloads or API keys are stored).
* Added a configurable contact source sent to Ecomail, defaulting to `magento_plugin`.
* Customer group tags are now sent without spaces, and the NOT LOGGED IN group is no longer sent.
* Existing Ecomail tags are now preserved when subscribing or updating a single contact.
* Added an unsaved-changes warning on the Ecomail configuration page.

## v2.2.0 - 21 May, 2026
* Added a Recent API Requests log for Ecomail calls in admin.

## v2.1.0 - 19 May, 2026
* Added support for modern PHP 8.x and Magento 2.4.x environments.
* The Ecomail API key is now stored encrypted.
* Added Ecomail form widget settings and product-view tracking.
* Added customer group tags and a MAGENTO_LANGUAGE custom field.
* Added synchronization of customer and address updates.
* Added a token-protected webhook so Ecomail can send subscription status changes back to the store.
* Added a CLI command for the initial customer and order synchronization.
* Fixed store-scope handling in order and subscription data.

## v2.0.0 - 22 Mar, 2021
* Initial release.
