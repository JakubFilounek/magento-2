# Ecomail for Magento 2

Connect a Magento 2 store with Ecomail for newsletter subscribers, customer data, orders, abandoned carts, product tracking, and subscription status updates.

## Features

- Synchronizes Magento newsletter subscribers with a selected Ecomail list.
- Sends a configurable Ecomail contact source. The default source is `magento_plugin`.
- Sends customer name, address, date of birth, customer group, and store language when enabled.
- Sends completed orders as Ecomail transactions.
- Sends cart contents when Magento knows the customer email.
- Adds optional Ecomail page view and product view tracking.
- Supports Magento cookie consent before loading Ecomail browser scripts.
- Shows an optional checkout newsletter opt-out checkbox.
- Provides a token-protected webhook endpoint for subscription status updates from Ecomail.
- Runs initial customer and order sync through Magento cron with visible progress.
- Shows recent Ecomail API request status in Magento admin without storing payloads or API keys.

## Composer Installation

After installing the package from Adobe Commerce Marketplace, enable the module from the Magento root directory:

```bash
php bin/magento module:enable Ecomail_Ecomail
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## Configuration

Open Magento admin and go to:

```text
Stores > Configuration > Ecomail
```

Enter the Ecomail API key, load subscriber lists, choose the destination list, configure the contact source, sync, and tracking options, then save the configuration.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.x.
- PHP 8.1, 8.2, 8.3, or 8.4, according to the Magento version in use.
- PHP cURL extension.
- Magento cron for background synchronization.
- OpenSearch or Elasticsearch configured as required by the Magento installation.

## Support

For help, contact Ecomail support at support@ecomail.cz.
