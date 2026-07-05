<?php

use Yeevy\CentrisPasserelle\Exceptions\ColumnMapMismatch;
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
use Yeevy\CentrisPasserelle\Validation\SnapshotValidator;

const SYNTHETIC_LISTINGS = __DIR__.'/fixtures/synthetic/listings.txt';

it('registers every parser as a container singleton', function (string $parser) {
    expect(app($parser))->toBeInstanceOf($parser)
        ->and(app($parser))->toBe(app($parser));
})->with([
    ListingsParser::class,
    RemarksParser::class,
    AddendaParser::class,
    PhotosParser::class,
    FeaturesParser::class,
    ExpensesParser::class,
    RenovationsParser::class,
    AdditionalLinksParser::class,
    OpenHousesParser::class,
    UnitsParser::class,
    RoomsParser::class,
    BrokersParser::class,
    FirmsParser::class,
    OfficesParser::class,
]);

it('parses a snapshot through the container-resolved parser', function () {
    $records = iterator_to_array(app(ListingsParser::class)->parseFile(SYNTHETIC_LISTINGS), false);

    expect($records)->toHaveCount(1)
        ->and($records[0]->mlsNumber)->toBe('9999999')
        ->and($records[0]->streetName)->toBe('Rue Exemple');
});

it('applies column overrides from config', function () {
    config()->set('centris.column_overrides.listings', ['street_name' => 29]); // postal code position

    $records = iterator_to_array(app(ListingsParser::class)->parseFile(SYNTHETIC_LISTINGS), false);

    expect($records[0]->streetName)->toBe('J0X0X0');
});

it('rejects malformed column overrides', function () {
    config()->set('centris.column_overrides.listings', ['street_name' => 'not-a-position']);

    app(ListingsParser::class);
})->throws(InvalidArgumentException::class, 'integer positions');

it('registers the snapshot validator with config thresholds', function () {
    config()->set('centris.validation.sample_size', 10);
    config()->set('centris.validation.failure_threshold', 0.0);

    $validator = app(SnapshotValidator::class);

    expect($validator)->toBeInstanceOf(SnapshotValidator::class);

    // threshold 0.0 → a single bad row among good ones must throw
    $validator->validateString("\"1234567\"\r\n\"not-numeric\"\r\n");
})->throws(ColumnMapMismatch::class);
