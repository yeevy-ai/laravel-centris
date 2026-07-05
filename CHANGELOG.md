# Changelog

All notable changes to `yeevy/laravel-centris` will be documented in this file.

## v0.1.0 — Initial release - 2026-07-05

Laravel wrapper for [yeevy/centris-passerelle](https://packagist.org/packages/yeevy/centris-passerelle), the unofficial client for the Centris® Passerelle FTP feed (Quebec MLS). Not affiliated with or endorsed by Centris or QFREB. Requires a valid diffusion agreement.

### Highlights

- **Container bindings** for all 14 feed parsers and the `SnapshotValidator`, built from `config/centris.php` — named column-map profiles, per-agreement position overrides, PSR-3 logging.
- **`centris:sync`** — full snapshot synchronization: configurable feed source (`local` directory or built-in **FTP fetch** via `CENTRIS_FTP_*` env vars, optional ZIP extraction), drift validation, dirty-hash upserts through your `ListingRepository` implementation, and removal reconciliation. Schedulable.
- **`centris:photos`** — queues one `DownloadPhoto` job per photo; downloads are content-addressed (sha256 dedupe) and fire `PhotoDownloaded` with the local path. Failures follow your queue's retry policy.
- **Laravel-native events** — the core's `ListingCreated` / `ListingUpdated` / `ListingRemoved` flow through Laravel's event system via a PSR-14 bridge.

### Requirements

PHP 8.2+, Laravel 12 or 13, `yeevy/centris-passerelle` ^0.3. Quality gates: larastan level 8, Pest (34 tests), Pint.

### Getting started

```bash
composer require yeevy/laravel-centris
php artisan vendor:publish --tag=centris-config

```
Bind `Yeevy\CentrisPasserelle\Contracts\ListingRepository` to your storage, set the `CENTRIS_*` env vars, and schedule `centris:sync` + `centris:photos`.
