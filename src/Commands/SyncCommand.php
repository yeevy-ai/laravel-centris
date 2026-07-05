<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;
use Yeevy\CentrisPasserelle\Contracts\FeedSource;
use Yeevy\CentrisPasserelle\Contracts\ListingRepository;
use Yeevy\CentrisPasserelle\Exceptions\ColumnMapMismatch;
use Yeevy\CentrisPasserelle\Sync\ListingsSynchronizer;

class SyncCommand extends Command
{
    protected $signature = 'centris:sync
        {--path= : Snapshot directory or listings file (defaults to the configured feed source)}
        {--file=INSCRIPTIONS.TXT : Listings filename inside the snapshot directory}';

    protected $description = 'Synchronize Centris Passerelle listings from a snapshot into your repository';

    public function handle(): int
    {
        if (! $this->laravel->bound(ListingRepository::class)) {
            $this->error(sprintf(
                'No listing repository bound. Bind %s to your storage implementation in a service provider.',
                ListingRepository::class,
            ));

            return self::FAILURE;
        }

        $path = $this->option('path');

        /** @var FeedSource|string $target */
        $target = is_string($path) && $path !== ''
            ? $path
            : $this->laravel->make(FeedSource::class); // config-driven: local dir or FTP fetch

        $file = $this->option('file');
        $file = is_string($file) && $file !== '' ? $file : 'INSCRIPTIONS.TXT';

        try {
            $result = $this->laravel->make(ListingsSynchronizer::class)->sync($target, $file);
        } catch (ColumnMapMismatch $exception) {
            $this->error('Snapshot rejected: '.$exception->getMessage());

            return self::FAILURE;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error('Sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Created', 'Updated', 'Skipped', 'Removed'],
            [[$result->created, $result->updated, $result->skipped, $result->removed]],
        );

        return self::SUCCESS;
    }
}
