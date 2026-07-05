<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Yeevy\CentrisPasserelle\Config\ColumnMap;
use Yeevy\CentrisPasserelle\Contracts\FeedSource;
use Yeevy\CentrisPasserelle\Contracts\ListingRepository;
use Yeevy\CentrisPasserelle\Parser\AddendaParser;
use Yeevy\CentrisPasserelle\Parser\AdditionalLinksParser;
use Yeevy\CentrisPasserelle\Parser\BrokersParser;
use Yeevy\CentrisPasserelle\Parser\ExpensesParser;
use Yeevy\CentrisPasserelle\Parser\FeaturesParser;
use Yeevy\CentrisPasserelle\Parser\FirmsParser;
use Yeevy\CentrisPasserelle\Parser\ListingsParser;
use Yeevy\CentrisPasserelle\Parser\OfficesParser;
use Yeevy\CentrisPasserelle\Parser\OpenHousesParser;
use Yeevy\CentrisPasserelle\Parser\PhotosParser;
use Yeevy\CentrisPasserelle\Parser\RemarksParser;
use Yeevy\CentrisPasserelle\Parser\RenovationsParser;
use Yeevy\CentrisPasserelle\Parser\RoomsParser;
use Yeevy\CentrisPasserelle\Parser\UnitsParser;
use Yeevy\CentrisPasserelle\Sync\ListingsSynchronizer;
use Yeevy\CentrisPasserelle\Validation\SnapshotValidator;
use Yeevy\LaravelCentris\Commands\SyncCommand;
use Yeevy\LaravelCentris\Events\LaravelEventDispatcher;
use Yeevy\LaravelCentris\Feed\FeedSourceFactory;

class CentrisServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('centris')
            ->hasConfigFile()
            ->hasCommand(SyncCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ListingsParser::class, fn (Application $app) => new ListingsParser($this->columnMap('listings'), $app->make(LoggerInterface::class)));
        $this->app->singleton(RemarksParser::class, fn (Application $app) => new RemarksParser($this->columnMap('remarks'), $app->make(LoggerInterface::class)));
        $this->app->singleton(AddendaParser::class, fn (Application $app) => new AddendaParser($this->columnMap('addenda'), $app->make(LoggerInterface::class)));
        $this->app->singleton(PhotosParser::class, fn (Application $app) => new PhotosParser($this->columnMap('photos'), $app->make(LoggerInterface::class)));
        $this->app->singleton(FeaturesParser::class, fn (Application $app) => new FeaturesParser($this->columnMap('features'), $app->make(LoggerInterface::class)));
        $this->app->singleton(ExpensesParser::class, fn (Application $app) => new ExpensesParser($this->columnMap('expenses'), $app->make(LoggerInterface::class)));
        $this->app->singleton(RenovationsParser::class, fn (Application $app) => new RenovationsParser($this->columnMap('renovations'), $app->make(LoggerInterface::class)));
        $this->app->singleton(AdditionalLinksParser::class, fn (Application $app) => new AdditionalLinksParser($this->columnMap('additional_links'), $app->make(LoggerInterface::class)));
        $this->app->singleton(OpenHousesParser::class, fn (Application $app) => new OpenHousesParser($this->columnMap('open_houses'), $app->make(LoggerInterface::class)));
        $this->app->singleton(UnitsParser::class, fn (Application $app) => new UnitsParser($this->columnMap('units'), $app->make(LoggerInterface::class)));
        $this->app->singleton(RoomsParser::class, fn (Application $app) => new RoomsParser($this->columnMap('rooms'), $app->make(LoggerInterface::class)));
        $this->app->singleton(BrokersParser::class, fn (Application $app) => new BrokersParser($this->columnMap('brokers'), $app->make(LoggerInterface::class)));
        $this->app->singleton(FirmsParser::class, fn (Application $app) => new FirmsParser($this->columnMap('firms'), $app->make(LoggerInterface::class)));
        $this->app->singleton(OfficesParser::class, fn (Application $app) => new OfficesParser($this->columnMap('offices'), $app->make(LoggerInterface::class)));

        $this->app->singleton(FeedSource::class, fn () => (new FeedSourceFactory)->make());

        $this->app->singleton(ListingsSynchronizer::class, fn (Application $app) => new ListingsSynchronizer(
            repository: $app->make(ListingRepository::class),
            parser: $app->make(ListingsParser::class),
            validator: $app->make(SnapshotValidator::class),
            events: new LaravelEventDispatcher($app->make(Dispatcher::class)),
            logger: $app->make(LoggerInterface::class),
        ));

        $this->app->singleton(SnapshotValidator::class, function (Application $app) {
            $sampleSize = config('centris.validation.sample_size', 50);
            $failureThreshold = config('centris.validation.failure_threshold', 0.5);

            return new SnapshotValidator(
                columns: $this->columnMap('listings'),
                sampleSize: is_numeric($sampleSize) ? (int) $sampleSize : 50,
                failureThreshold: is_numeric($failureThreshold) ? (float) $failureThreshold : 0.5,
                logger: $app->make(LoggerInterface::class),
            );
        });
    }

    /**
     * Column map for a file type: shipped default (or the configured
     * named profile) plus per-agreement position overrides.
     */
    private function columnMap(string $key): ColumnMap
    {
        $profile = config("centris.column_profiles.{$key}");
        $profile = is_string($profile) ? $profile : null;

        $map = match ($key) {
            'listings' => ColumnMap::listings($profile),
            'remarks' => ColumnMap::remarks($profile),
            'addenda' => ColumnMap::addenda($profile),
            'photos' => ColumnMap::photos($profile),
            'features' => ColumnMap::features($profile),
            'expenses' => ColumnMap::expenses($profile),
            'renovations' => ColumnMap::renovations($profile),
            'additional_links' => ColumnMap::additionalLinks($profile),
            'open_houses' => ColumnMap::openHouses($profile),
            'units' => ColumnMap::units($profile),
            'rooms' => ColumnMap::rooms($profile),
            'brokers' => ColumnMap::brokers($profile),
            'firms' => ColumnMap::firms($profile),
            'offices' => ColumnMap::offices($profile),
            default => throw new InvalidArgumentException("Unknown column map key: {$key}"),
        };

        $overrides = config("centris.column_overrides.{$key}", []);

        if (! is_array($overrides) || $overrides === []) {
            return $map;
        }

        $validated = [];

        foreach ($overrides as $field => $position) {
            if (! is_string($field) || ! is_int($position)) {
                throw new InvalidArgumentException(
                    "centris.column_overrides.{$key} must map field names to integer positions."
                );
            }

            $validated[$field] = $position;
        }

        return $map->with($validated);
    }
}
