# Ecomail changelog

## v2.3.0 - 11 June, 2026
* added etc/db_schema_whitelist.json for declarative schema support
* added full OSL-3.0 license text to LICENSE.txt
* declared explicit Composer dependencies on the store, quote, backend, and config modules
* fixed admin template output escaping and translation handling for Marketplace code standards
* fixed coding standard issues: line lengths, constant visibility, and nullable parameter declarations
* removed unused legacy CLI sync code paths
* changed initial sync to a Magento cron-backed batch job
* added persistent sync progress and admin start/status panel
* added lock handling so another sync cannot start while one is pending or running
* added compact Ecomail API request logging with retention cleanup
* added database tables for sync status and API logs
* preserved existing Ecomail tags during single subscriber subscribe and update requests
* added a floating unsaved changes warning to the Ecomail configuration page
* normalized Magento customer group tags and skipped the NOT LOGGED IN group
* added configurable Ecomail contact source with magento_plugin as the default
* added an admin show/hide toggle for the API key field

## v2.2.0 - 21 May, 2026
* prepared Magento cron-backed initial sync foundation
* added compact API request logging

## v2.1.0 - 19 May, 2026
* updated Composer metadata for modern PHP 8.x / Magento 2.4.x installations
* replaced legacy Zend HTTP transport and removed global Magento HTTP preferences
* added encrypted API key config handling
* added Ecomail form widget settings and product-view tracking
* added customer group tags and MAGENTO_LANGUAGE custom field support
* added customer and address update synchronization
* added token-protected inbound subscriber webhook
* added CLI command for initial customer and order synchronization
* fixed store-scope usage in order and subscription mapping

## v2.0.0 - 22 Mar, 2021
* initial release
