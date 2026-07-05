<?php

use Yeevy\CentrisPasserelle\Contracts\FeedSource;
use Yeevy\CentrisPasserelle\Contracts\ListingRepository;
use Yeevy\CentrisPasserelle\Feed\FlysystemFeedSource;
use Yeevy\CentrisPasserelle\Feed\LocalDirectorySource;
use Yeevy\CentrisPasserelle\Feed\ZipExtractingSource;
use Yeevy\LaravelCentris\Tests\Support\InMemoryListingRepository;

it('binds a local source by default', function () {
    expect(app(FeedSource::class))->toBeInstanceOf(LocalDirectorySource::class);
});

it('wraps the source with archive extraction when configured', function () {
    config()->set('centris.extract_archives', true);

    expect(app(FeedSource::class))->toBeInstanceOf(ZipExtractingSource::class);
});

it('builds an FTP-backed source from config', function () {
    config()->set('centris.source', 'ftp');
    config()->set('centris.ftp.host', 'ftp.example.test');
    config()->set('centris.ftp.username', 'broker');
    config()->set('centris.ftp.password', 'secret');

    expect(app(FeedSource::class))->toBeInstanceOf(FlysystemFeedSource::class);
});

it('demands FTP credentials for the ftp source', function () {
    config()->set('centris.source', 'ftp');

    app(FeedSource::class);
})->throws(InvalidArgumentException::class, 'CENTRIS_FTP_');

it('rejects unknown source drivers', function () {
    config()->set('centris.source', 'dropbox');

    app(FeedSource::class);
})->throws(InvalidArgumentException::class, "Unknown centris.source driver 'dropbox'");

it('syncs through the configured source when no --path is given', function () {
    config()->set('centris.feed_path', __DIR__.'/fixtures/synthetic');

    $repository = new InMemoryListingRepository;
    $this->app->instance(ListingRepository::class, $repository);

    $this->artisan('centris:sync', ['--file' => 'listings.txt'])->assertSuccessful();

    expect($repository->saved)->toHaveKey('9999999');
});

it('reports a missing feed directory cleanly', function () {
    config()->set('centris.feed_path', '/nonexistent/feed');

    $this->app->instance(ListingRepository::class, new InMemoryListingRepository);

    $this->artisan('centris:sync')
        ->expectsOutputToContain('Sync failed')
        ->assertFailed();
});
