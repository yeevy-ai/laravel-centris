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

### Synchronisation

Implémentez `ListingRepository` contre votre stockage et liez-la dans un fournisseur de services :

```php
$this->app->singleton(
    \Yeevy\CentrisPasserelle\Contracts\ListingRepository::class,
    EloquentListingRepository::class,
);
```

Puis lancez la synchronisation (validation de dérive, upsert avec saut des lignes inchangées, réconciliation des retraits) :

```bash
php artisan centris:sync                 # utilise centris.feed_path
php artisan centris:sync --path=/depot   # dossier ou fichier explicite
```

Les événements du noyau traversent le système d'événements Laravel — écoutez-les comme d'habitude :

```php
Event::listen(function (\Yeevy\CentrisPasserelle\Events\ListingRemoved $event) {
    // dépublier $event->mlsNumber
});
```

Planifiez-la comme n'importe quelle commande : `Schedule::command('centris:sync')->twiceDaily(6, 18);`

### Récupération FTP

Sans `--path`, `centris:sync` utilise la source configurée. Réglez `CENTRIS_SOURCE=ftp` et la commande télécharge d'abord le dépôt Passerelle dans `feed_path` :

```dotenv
CENTRIS_SOURCE=ftp
CENTRIS_FTP_HOST=ftp.example.test
CENTRIS_FTP_USERNAME=votre-code
CENTRIS_FTP_PASSWORD=********
CENTRIS_EXTRACT_ARCHIVES=true   # si votre entente livre un ZIP
```

Pour SFTP ou une source sur mesure, reliez à nouveau le contrat `FeedSource` dans votre fournisseur de services.

### Photos en file d'attente

`centris:photos` met en file un job par photo de `PHOTOS.TXT` ; chaque job télécharge dans des fichiers adressés par contenu (`{sha256}.jpg` — les octets identiques ne sont stockés qu'une fois) puis déclenche `PhotoDownloaded` :

```bash
php artisan centris:photos
```

```php
Event::listen(function (\Yeevy\LaravelCentris\Events\PhotoDownloaded $event) {
    // associer $event->photo->path à l'inscription $event->photo->photo->mlsNumber
});
```

### Configuration

`config/centris.php` :

- `feed_path` — répertoire des fichiers d'instantané (`CENTRIS_FEED_PATH`)
- `source` / `ftp` / `extract_archives` — provenance de l'instantané pour `centris:sync`
- `photos` — répertoire des photos (`CENTRIS_PHOTOS_PATH`) et file d'attente (`CENTRIS_PHOTOS_QUEUE`)
- `column_profiles` — profil de carte nommé par type de fichier
- `column_overrides` — surcharges de positions propres à votre entente, p. ex. `'listings' => ['status_code' => 118]`
- `validation` — seuils de la détection de dérive

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

### Synchronization

Implement `ListingRepository` against your storage and bind it in a service provider:

```php
$this->app->singleton(
    \Yeevy\CentrisPasserelle\Contracts\ListingRepository::class,
    EloquentListingRepository::class,
);
```

Then run the sync (drift validation, dirty-hash upserts, removal reconciliation):

```bash
php artisan centris:sync                 # uses centris.feed_path
php artisan centris:sync --path=/drop    # explicit directory or file
```

The core events flow through Laravel's event system — listen as usual:

```php
Event::listen(function (\Yeevy\CentrisPasserelle\Events\ListingRemoved $event) {
    // unpublish $event->mlsNumber
});
```

Schedule it like any command: `Schedule::command('centris:sync')->twiceDaily(6, 18);`

### FTP fetch

Without `--path`, `centris:sync` uses the configured source. Set `CENTRIS_SOURCE=ftp` and the command downloads the Passerelle drop into `feed_path` first:

```dotenv
CENTRIS_SOURCE=ftp
CENTRIS_FTP_HOST=ftp.example.test
CENTRIS_FTP_USERNAME=your-code
CENTRIS_FTP_PASSWORD=********
CENTRIS_EXTRACT_ARCHIVES=true   # if your agreement delivers a ZIP
```

For SFTP or a custom source, rebind the `FeedSource` contract in your service provider.

### Queued photos

`centris:photos` queues one job per `PHOTOS.TXT` row; each job downloads into content-addressed files (`{sha256}.jpg` — identical bytes are stored once) and fires `PhotoDownloaded`:

```bash
php artisan centris:photos
```

```php
Event::listen(function (\Yeevy\LaravelCentris\Events\PhotoDownloaded $event) {
    // associate $event->photo->path with listing $event->photo->photo->mlsNumber
});
```

### Configuration

`config/centris.php`:

- `feed_path` — snapshot files directory (`CENTRIS_FEED_PATH`)
- `source` / `ftp` / `extract_archives` — where `centris:sync` gets the snapshot
- `photos` — photo directory (`CENTRIS_PHOTOS_PATH`) and queue (`CENTRIS_PHOTOS_QUEUE`)
- `column_profiles` — named map profile per file type
- `column_overrides` — per-agreement position overrides, e.g. `'listings' => ['status_code' => 118]`
- `validation` — drift-detection thresholds

### License

[MIT](LICENSE.md) — © Digital Unity Inc. ([Yeevy](https://yeevy.ai))
