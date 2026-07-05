<?php

use Illuminate\Support\Facades\Event;
use Yeevy\CentrisPasserelle\Contracts\ListingRepository;
use Yeevy\CentrisPasserelle\Events\ListingCreated;
use Yeevy\CentrisPasserelle\Events\ListingRemoved;
use Yeevy\LaravelCentris\Tests\Support\InMemoryListingRepository;

const SNAPSHOT_DIR = __DIR__.'/fixtures/synthetic';

it('fails with guidance when no repository is bound', function () {
    $this->artisan('centris:sync', ['--path' => SNAPSHOT_DIR])
        ->expectsOutputToContain('No listing repository bound')
        ->assertFailed();
});

it('syncs a snapshot into the bound repository', function () {
    $repository = new InMemoryListingRepository;
    $this->app->instance(ListingRepository::class, $repository);

    $this->artisan('centris:sync', ['--path' => SNAPSHOT_DIR, '--file' => 'listings.txt'])
        ->assertSuccessful();

    expect($repository->saved)->toHaveKey('9999999');
});

it('dispatches core events through Laravel', function () {
    Event::fake([ListingCreated::class, ListingRemoved::class]);

    $repository = new InMemoryListingRepository;
    $repository->hashes['1111111'] = 'gone-from-snapshot';
    $this->app->instance(ListingRepository::class, $repository);

    $this->artisan('centris:sync', ['--path' => SNAPSHOT_DIR, '--file' => 'listings.txt'])
        ->assertSuccessful();

    Event::assertDispatched(ListingCreated::class, fn (ListingCreated $e) => $e->listing->mlsNumber === '9999999');
    Event::assertDispatched(ListingRemoved::class, fn (ListingRemoved $e) => $e->mlsNumber === '1111111');
});

it('rejects a drifted snapshot without writing', function () {
    config()->set('centris.column_overrides.listings', ['mls_number' => 27]); // street name position

    $repository = new InMemoryListingRepository;
    $this->app->instance(ListingRepository::class, $repository);

    $this->artisan('centris:sync', ['--path' => SNAPSHOT_DIR, '--file' => 'listings.txt'])
        ->expectsOutputToContain('Snapshot rejected')
        ->assertFailed();

    expect($repository->saved)->toBe([]);
});
