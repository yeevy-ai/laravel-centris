<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Yeevy\CentrisPasserelle\Dto\PhotoRecord;
use Yeevy\CentrisPasserelle\Photo\PhotoDownloader;
use Yeevy\LaravelCentris\Events\PhotoDownloaded;

/**
 * Downloads one listing photo on the queue. PhotoDownloadFailed lets
 * the job fail, so the queue's retry/backoff policy applies; success
 * fires PhotoDownloaded with the content-addressed local path.
 */
class DownloadPhoto implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public PhotoRecord $photo,
    ) {}

    public function handle(PhotoDownloader $downloader, Dispatcher $events): void
    {
        $events->dispatch(new PhotoDownloaded($downloader->download($this->photo)));
    }
}
