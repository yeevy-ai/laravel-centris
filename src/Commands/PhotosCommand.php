<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Commands;

use Illuminate\Console\Command;
use Yeevy\CentrisPasserelle\Parser\PhotosParser;
use Yeevy\LaravelCentris\Jobs\DownloadPhoto;

class PhotosCommand extends Command
{
    protected $signature = 'centris:photos
        {--path= : Snapshot directory or photos file (defaults to centris.feed_path)}
        {--file=PHOTOS.TXT : Photos filename inside the snapshot directory}';

    protected $description = 'Queue a download job for every photo in the snapshot';

    public function handle(PhotosParser $parser): int
    {
        $path = $this->option('path') ?? config('centris.feed_path');

        if (! is_string($path) || $path === '') {
            $this->error('No snapshot path — pass --path or set centris.feed_path.');

            return self::FAILURE;
        }

        $file = $this->option('file');
        $file = is_string($file) && $file !== '' ? $file : 'PHOTOS.TXT';

        if (is_dir($path)) {
            $path = rtrim($path, '/').'/'.$file;
        }

        if (! is_file($path)) {
            $this->error("Photos file not found: {$path}");

            return self::FAILURE;
        }

        $queue = config('centris.photos.queue');
        $queued = 0;

        foreach ($parser->parseFile($path) as $photo) {
            $job = new DownloadPhoto($photo);

            if (is_string($queue) && $queue !== '') {
                $job->onQueue($queue);
            }

            dispatch($job);
            $queued++;
        }

        $this->info("Queued {$queued} photo download(s).");

        return self::SUCCESS;
    }
}
