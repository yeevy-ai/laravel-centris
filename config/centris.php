<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Feed path
    |--------------------------------------------------------------------------
    |
    | Directory where Passerelle snapshot files (INSCRIPTIONS.TXT, …) are
    | dropped or extracted. Used by the upcoming centris:sync command.
    |
    */

    'feed_path' => env('CENTRIS_FEED_PATH', storage_path('app/centris')),

    /*
    |--------------------------------------------------------------------------
    | Column map profiles
    |--------------------------------------------------------------------------
    |
    | Named profile per file type when Centris ships a new layout, e.g.
    | 'listings' => '2027'. Omitted keys use the shipped default map.
    |
    */

    'column_profiles' => [],

    /*
    |--------------------------------------------------------------------------
    | Column position overrides
    |--------------------------------------------------------------------------
    |
    | Shipped positions are community-observed — verify against YOUR
    | Passerelle documentation and override per file type, e.g.
    | 'listings' => ['status_code' => 118].
    |
    | Keys: listings, remarks, addenda, photos, features, expenses,
    | renovations, additional_links, open_houses, units, rooms,
    | brokers, firms, offices.
    |
    */

    'column_overrides' => [],

    /*
    |--------------------------------------------------------------------------
    | Snapshot validation
    |--------------------------------------------------------------------------
    |
    | Drift-detection thresholds used by the SnapshotValidator binding.
    |
    */

    'validation' => [
        'sample_size' => 50,
        'failure_threshold' => 0.5,
    ],

];
