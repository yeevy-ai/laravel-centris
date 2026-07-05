<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Feed;

use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use Yeevy\CentrisPasserelle\Contracts\FeedSource;
use Yeevy\CentrisPasserelle\Feed\FlysystemFeedSource;
use Yeevy\CentrisPasserelle\Feed\LocalDirectorySource;
use Yeevy\CentrisPasserelle\Feed\ZipExtractingSource;

/**
 * Builds the FeedSource centris:sync uses when no --path is given,
 * from the centris.source / centris.ftp / centris.extract_archives
 * config. Rebind the FeedSource contract to use anything else (e.g.
 * SFTP via league/flysystem-sftp-v3).
 */
final class FeedSourceFactory
{
    public function make(): FeedSource
    {
        $feedPath = config('centris.feed_path');

        if (! is_string($feedPath) || $feedPath === '') {
            throw new InvalidArgumentException('centris.feed_path must be a non-empty path.');
        }

        $driver = config('centris.source', 'local');

        $source = match ($driver) {
            'local' => new LocalDirectorySource($feedPath),
            'ftp' => $this->ftp($feedPath),
            default => throw new InvalidArgumentException(sprintf(
                "Unknown centris.source driver '%s' — use 'local' or 'ftp', or rebind %s.",
                is_scalar($driver) ? (string) $driver : gettype($driver),
                FeedSource::class,
            )),
        };

        return config('centris.extract_archives') === true
            ? new ZipExtractingSource($source)
            : $source;
    }

    private function ftp(string $localDirectory): FlysystemFeedSource
    {
        $options = config('centris.ftp');

        if (! is_array($options)) {
            throw new InvalidArgumentException('centris.ftp must be an array.');
        }

        foreach (['host', 'username', 'password'] as $required) {
            if (! is_string($options[$required] ?? null) || $options[$required] === '') {
                throw new InvalidArgumentException(
                    "centris.ftp.{$required} is required for the ftp source — set the CENTRIS_FTP_* environment variables."
                );
            }
        }

        $options['root'] = is_string($options['root'] ?? null) && $options['root'] !== '' ? $options['root'] : '/';

        return new FlysystemFeedSource(
            new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($options))),
            localDirectory: $localDirectory,
        );
    }
}
