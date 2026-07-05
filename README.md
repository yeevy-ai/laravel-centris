# laravel-centris

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yeevy/laravel-centris.svg?style=flat-square)](https://packagist.org/packages/yeevy/laravel-centris)
[![Tests](https://img.shields.io/github/actions/workflow/status/yeevy-ai/laravel-centris/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/yeevy-ai/laravel-centris/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/yeevy/laravel-centris.svg?style=flat-square)](https://packagist.org/packages/yeevy/laravel-centris)

> **Non officiel. Aucune affiliation avec Centris ou l'APCIQ/QFREB, ni endossement de leur part. Requiert une entente de diffusion valide.**
>
> **Unofficial. Not affiliated with or endorsed by Centris or QFREB. Requires a valid diffusion agreement.**

[Français](#français) | [English](#english)

---

## Français

Enveloppe Laravel pour [`yeevy/centris-passerelle`](https://github.com/yeevy-ai/centris-passerelle), le client PHP non officiel du flux FTP **Centris® Passerelle** (inscriptions MLS du Québec). Fournit la configuration, l'injection de dépendances et — à venir — la commande de synchronisation, les jobs en file d'attente et les événements.

### Installation

```bash
composer require yeevy/laravel-centris

php artisan vendor:publish --tag="centris-config"
```

### Utilisation

Les 14 analyseurs et le validateur de dérive sont des singletons du conteneur, prêts à injecter :

```php
use Yeevy\CentrisPasserelle\Parser\ListingsParser;
use Yeevy\CentrisPasserelle\Validation\SnapshotValidator;

class SyncListings
{
    public function __construct(
        private ListingsParser $parser,
        private SnapshotValidator $validator,
    ) {}

    public function handle(): void
    {
        $file = config('centris.feed_path').'/INSCRIPTIONS.TXT';

        $this->validator->validateFile($file); // lève ColumnMapMismatch si la structure a dérivé

        foreach ($this->parser->parseFile($file) as $listing) {
            // $listing->mlsNumber, $listing->status, $listing->dirtyHash…
        }
    }
}
```

### Configuration

`config/centris.php` :

- `feed_path` — répertoire des fichiers d'instantané (`CENTRIS_FEED_PATH`)
- `column_profiles` — profil de carte nommé par type de fichier
- `column_overrides` — surcharges de positions propres à votre entente, p. ex. `'listings' => ['status_code' => 118]`
- `validation` — seuils de la détection de dérive

### Feuille de route

- Commande `centris:sync` (récupération FTP, upsert, réconciliation) — dépend du pipeline du paquet noyau
- Jobs en file d'attente pour les photos, événements `ListingCreated` / `ListingUpdated` / `ListingRemoved`

### Licence

[MIT](LICENSE.md) — © Digital Unity Inc. ([Yeevy](https://yeevy.ai))

---

## English

Laravel wrapper for [`yeevy/centris-passerelle`](https://github.com/yeevy-ai/centris-passerelle), the unofficial PHP client for the **Centris® Passerelle** FTP feed (Quebec MLS listings). Provides configuration, dependency injection, and — upcoming — the sync command, queued jobs, and events.

### Installation

```bash
composer require yeevy/laravel-centris

php artisan vendor:publish --tag="centris-config"
```

### Usage

All 14 parsers and the drift validator are container singletons, ready to inject:

```php
use Yeevy\CentrisPasserelle\Parser\ListingsParser;
use Yeevy\CentrisPasserelle\Validation\SnapshotValidator;

class SyncListings
{
    public function __construct(
        private ListingsParser $parser,
        private SnapshotValidator $validator,
    ) {}

    public function handle(): void
    {
        $file = config('centris.feed_path').'/INSCRIPTIONS.TXT';

        $this->validator->validateFile($file); // throws ColumnMapMismatch on structure drift

        foreach ($this->parser->parseFile($file) as $listing) {
            // $listing->mlsNumber, $listing->status, $listing->dirtyHash…
        }
    }
}
```

### Configuration

`config/centris.php`:

- `feed_path` — snapshot files directory (`CENTRIS_FEED_PATH`)
- `column_profiles` — named map profile per file type
- `column_overrides` — per-agreement position overrides, e.g. `'listings' => ['status_code' => 118]`
- `validation` — drift-detection thresholds

### Roadmap

- `centris:sync` command (FTP fetch, upsert, reconciliation) — depends on the core package's pipeline
- Queued photo jobs, `ListingCreated` / `ListingUpdated` / `ListingRemoved` events

### License

[MIT](LICENSE.md) — © Digital Unity Inc. ([Yeevy](https://yeevy.ai))
