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
    | Feed source
    |--------------------------------------------------------------------------
    |
    | Where centris:sync gets the snapshot when no --path is given.
    | 'local' reads feed_path as-is; 'ftp' downloads the drop from the
    | Passerelle FTP account into feed_path first. Set extract_archives
    | to true when your agreement delivers the drop as a ZIP.
    |
    */

    'source' => env('CENTRIS_SOURCE', 'local'),

    'ftp' => [
        'host' => env('CENTRIS_FTP_HOST'),
        'root' => env('CENTRIS_FTP_ROOT', '/'),
        'username' => env('CENTRIS_FTP_USERNAME'),
        'password' => env('CENTRIS_FTP_PASSWORD'),
        'port' => (int) env('CENTRIS_FTP_PORT', 21),
        'ssl' => (bool) env('CENTRIS_FTP_SSL', false),
        'passive' => (bool) env('CENTRIS_FTP_PASSIVE', true),
        'timeout' => (int) env('CENTRIS_FTP_TIMEOUT', 30),
    ],

    'extract_archives' => (bool) env('CENTRIS_EXTRACT_ARCHIVES', false),

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
