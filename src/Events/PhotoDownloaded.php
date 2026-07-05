<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Events;

use Yeevy\CentrisPasserelle\Photo\DownloadedPhoto;

/**
 * A photo finished downloading (or deduplicated against existing
 * bytes). Listen to associate $photo->path with the listing.
 */
final readonly class PhotoDownloaded
{
    public function __construct(
        public DownloadedPhoto $photo,
    ) {}
}
