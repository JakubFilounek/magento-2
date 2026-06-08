# Ecomail for Magento 2 - User Guide

## Overview

Ecomail for Magento 2 connects a Magento store with an Ecomail account. It helps synchronize newsletter contacts, customer details, order transactions, cart data, and selected storefront tracking events.

## Requirements

| Requirement | Notes |
| --- | --- |
| Magento | Magento Open Source or Adobe Commerce 2.4.x |
| PHP | PHP version supported by the installed Magento version |
| PHP cURL | Required for communication with the Ecomail API |
| Magento cron | Required for background synchronization |
| Ecomail account | Required for API key, contact lists, and tracking |

## Installation

Run these commands from the Magento root folder:

```bash
composer require ecomail/magento2-ecomail
php bin/magento module:enable Ecomail_Ecomail
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

On some hosting providers, commands must be started with the full PHP path. The hosting provider can confirm the correct path.

## Configuration

Open Magento admin and go to:

```text
Stores > Configuration > Ecomail
```

| Setting Area | What It Does |
| --- | --- |
| Connection | Stores the Ecomail API key and loads available Ecomail lists |
| Contact Sync | Controls which customer fields and contact source are sent to Ecomail |
| Initial Sync | Imports existing Magento contacts and orders through Magento cron |
| Checkout | Shows an optional newsletter opt-out checkbox in checkout |
| Tracking | Enables page, product, cart, and order tracking |
| Webhook | Provides an endpoint for subscription status updates from Ecomail |
| API Logs | Shows recent Ecomail API request results |

After changing settings, save the configuration.

The API key field is stored encrypted by Magento after saving. The eye button beside the field can temporarily show the value currently displayed in the browser input, which is useful when checking a newly typed key.

## Contact Source

The extension sends a source value with synchronized contacts. The default source is:

```text
magento_plugin
```

The source can be changed in Magento admin if the store owner wants to use a different source name in Ecomail.

## Initial Synchronization

The initial sync imports existing Magento customers and orders in the background.

1. Enable the required sync options.
2. Save the configuration.
3. Click **Start Sync**.
4. Keep Magento cron running.
5. Check the progress panel in the Ecomail configuration page.

The sync runs in batches, so refreshing the page does not reset the progress.

## Tags

If tag synchronization is enabled, the extension can send Magento-related tags to Ecomail contacts. Tags are normalized so they do not contain spaces.

When updating individual subscribers, the extension checks the existing Ecomail contact first and adds Magento tags to the current tag list where possible. Bulk synchronization has a separate setting because Ecomail bulk imports can replace tags for existing contacts.

## Checkout Opt-Out

The extension can show a checkbox in checkout that allows the customer to skip newsletter synchronization for that order. The checkbox label can be changed in the Magento configuration.

## Webhook

The webhook endpoint is used by Ecomail to send subscription status changes back to Magento. The endpoint includes a generated token so only valid webhook calls are accepted.

Copy the generated webhook URL from Magento admin and use it in the Ecomail webhook settings.

## API Logs

Recent Ecomail API requests are shown in Magento admin. The log is intentionally compact and does not store API keys or full payloads.

## Updating

Run these commands from the Magento root folder:

```bash
composer require ecomail/magento2-ecomail
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

If the module was disabled before the update, enable it first:

```bash
php bin/magento module:enable Ecomail_Ecomail
```

## Troubleshooting

| Problem | What To Check |
| --- | --- |
| Lists do not load | Check the Ecomail API key and internet access from the server |
| Initial sync stays pending | Check that Magento cron is running |
| Tracking does not appear | Check tracking settings, cookie consent settings, and browser console |
| Webhook does not update status | Check the webhook URL, token, and selected store |
| Orders are not imported | Check recent API logs and whether required order data is available |

For help, contact Ecomail support at support@ecomail.cz.
