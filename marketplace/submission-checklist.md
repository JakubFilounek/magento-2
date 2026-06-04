# Adobe Commerce Marketplace Submission Checklist

## Package

- [ ] Build package ZIP with `tools/build-marketplace-package.ps1`.
- [ ] Confirm ZIP root contains `composer.json`, `registration.php`, `etc/`, `Block/`, `Model/`, and other module directories.
- [ ] Confirm ZIP does not include `.git`, `.github`, `docs`, `PRESTASHOP_PLUGIN`, `marketplace`, or local test files.
- [ ] Confirm `composer.json` includes `name`, `type`, and `version`.
- [ ] Confirm package size is below Adobe Marketplace limits.

## Technical Review

- [ ] Run PHP syntax checks.
- [ ] Run JavaScript syntax checks.
- [ ] Run Composer validation.
- [ ] Run Magento coding standard / Marketplace EQP code sniffer.
- [ ] Install on a clean Magento 2.4.x instance through Composer.
- [ ] Test `module:enable`, `setup:upgrade`, `setup:di:compile`, `setup:static-content:deploy -f`, and `cache:flush`.
- [ ] Test newsletter subscribe and unsubscribe.
- [ ] Test customer create/update.
- [ ] Test guest and registered checkout.
- [ ] Test order transaction sync.
- [ ] Test initial sync with Magento cron.
- [ ] Test webhook token generation and inbound subscription status update.
- [ ] Test storefront tracking with and without Magento cookie consent.
- [ ] Confirm no API keys, tokens, test domains, or private notes are included.

## Marketplace Portal

- [ ] Create free extension listing in Adobe Commerce Marketplace Developer Portal.
- [ ] Upload package ZIP.
- [ ] Select supported Magento versions.
- [ ] Add release notes.
- [ ] Export `marketplace/user-guide.md` to PDF and add it as the installation/user guide.
- [ ] Add screenshots for admin configuration and sync status.
- [ ] Add support email and support links.
- [ ] Submit for technical review.
- [ ] Submit for marketing review.
