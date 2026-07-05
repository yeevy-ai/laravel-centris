<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Yeevy\CentrisPasserelle\Parser\PhotosParser;
use Yeevy\CentrisPasserelle\Photo\PhotoDownloader;
use Yeevy\LaravelCentris\Events\PhotoDownloaded;
use Yeevy\LaravelCentris\Jobs\DownloadPhoto;

it('queues one download job per photo', function () {
    Queue::fake();

    $this->artisan('centris:photos', [
        '--path' => __DIR__.'/fixtures/synthetic',
        '--file' => 'photos.txt',
    ])->expectsOutputToContain('Queued 2 photo download(s).')
        ->assertSuccessful();

    Queue::assertPushed(DownloadPhoto::class, 2);
});

it('queues onto the configured queue', function () {
    config()->set('centris.photos.queue', 'photos');
    Queue::fake();

    $this->artisan('centris:photos', [
        '--path' => __DIR__.'/fixtures/synthetic',
        '--file' => 'photos.txt',
    ])->assertSuccessful();

    Queue::assertPushedOn('photos', DownloadPhoto::class);
});

it('fails cleanly when the photos file is missing', function () {
    $this->artisan('centris:photos', ['--path' => __DIR__.'/fixtures/synthetic', '--file' => 'missing.txt'])
        ->expectsOutputToContain('Photos file not found')
        ->assertFailed();
});

it('downloads and fires PhotoDownloaded when the job runs', function () {
    $directory = sys_get_temp_dir().'/centris-wrapper-photos-'.uniqid();

    $this->app->instance(PhotoDownloader::class, new PhotoDownloader(
        client: new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes'),
        ]))]),
        requestFactory: new HttpFactory,
        directory: $directory,
    ));

    Event::fake([PhotoDownloaded::class]);

    $photos = iterator_to_array(
        app(PhotosParser::class)->parseFile(__DIR__.'/fixtures/synthetic/photos.txt'),
        false,
    );

    (new DownloadPhoto($photos[0]))->handle(app(PhotoDownloader::class), app(Dispatcher::class));

    Event::assertDispatched(PhotoDownloaded::class, function (PhotoDownloaded $event) {
        return $event->photo->photo->mlsNumber === '9999999'
            && is_file($event->photo->path);
    });

    array_map(unlink(...), glob($directory.'/*') ?: []);
    rmdir($directory);
});
