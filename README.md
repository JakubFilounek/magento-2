# Ecomail Magento 2 Plugin

Magento 2 module for connecting a Magento store with Ecomail. The module synchronizes newsletter subscribers, customers, orders, checkout opt-out preferences, and selected tracking events.

## Main Features

- Connects Magento 2 to an Ecomail account using an API key.
- Lets the store owner choose an Ecomail subscriber list from Magento admin.
- Synchronizes existing customers and orders through Magento cron.
- Sends new newsletter signups and customer updates to Ecomail.
- Sends order transactions to Ecomail.
- Adds an optional checkout newsletter opt-out checkbox.
- Supports Ecomail tracking for page views, product views, carts, and orders.
- Provides a webhook endpoint for subscription status updates from Ecomail.
- Shows sync status and recent API request logs in Magento admin.
- Includes settings for tags and updating existing contacts during bulk sync.

## Compatibility

- Magento 2.4.x
- PHP 8.1 to 8.4
- Requires PHP cURL extension
- Requires Magento cron to be running

## Installation

When installed from Adobe Commerce Marketplace, use Composer from the Magento root folder:

```bash
composer require ecomail/magento2-ecomail:2.3.0
```

Then run:

```bash
php bin/magento module:enable Ecomail_Ecomail
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

For a manual development install, copy the module folder to:

```bash
app/code/Ecomail/Ecomail
```

Then run these commands from the Magento root folder:

```bash
php bin/magento module:enable Ecomail_Ecomail
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

After installation, open Magento admin and go to:

```text
Stores > Configuration > Ecomail
```

Enter the Ecomail API key, choose the subscriber list, configure the options, and save the settings.

## Updating

When installed through Composer, update with:

```bash
composer require ecomail/magento2-ecomail:2.3.0
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

For a manual development update, upload the new version of the module over the existing files in:

```bash
app/code/Ecomail/Ecomail
```

Then run:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

If the module was disabled before the update, enable it first:

```bash
php bin/magento module:enable Ecomail_Ecomail
```

## Cron

Magento cron must be active for background synchronization. A typical cron command looks like this:

```bash
php /path/to/magento/bin/magento cron:run
```

The exact PHP path and Magento path depend on the hosting provider.
