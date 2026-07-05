<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Commands;

use Illuminate\Console\Command;
use Yeevy\CentrisPasserelle\Contracts\ListingRepository;
use Yeevy\CentrisPasserelle\Exceptions\ColumnMapMismatch;
use Yeevy\CentrisPasserelle\Sync\ListingsSynchronizer;

class SyncCommand extends Command
{
    protected $signature = 'centris:sync
        {--path= : Snapshot directory or listings file (defaults to centris.feed_path)}
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

        $path = $this->option('path') ?? config('centris.feed_path');

        if (! is_string($path) || $path === '') {
            $this->error('No snapshot path — pass --path or set centris.feed_path.');

            return self::FAILURE;
        }

        $file = $this->option('file');
        $file = is_string($file) && $file !== '' ? $file : 'INSCRIPTIONS.TXT';

        try {
            $result = $this->laravel->make(ListingsSynchronizer::class)->sync($path, $file);
        } catch (ColumnMapMismatch $exception) {
            $this->error('Snapshot rejected: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Created', 'Updated', 'Skipped', 'Removed'],
            [[$result->created, $result->updated, $result->skipped, $result->removed]],
        );

        return self::SUCCESS;
    }
}
