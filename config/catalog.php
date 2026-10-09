<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalog Cache
    |--------------------------------------------------------------------------
    |
    | Storefront listing results and the filter option sets behind them are
    | cached so the browse pages stay fast under traffic. Entries are keyed
    | by a catalog version that is bumped whenever a vehicle, vehicle image
    | or taxonomy record changes, which is what invalidates a published
    | listing change straight away.
    |
    */

    'cache' => [
        'enabled' => (bool) env('CATALOG_CACHE_ENABLED', true),
        'ttl' => (int) env('CATALOG_CACHE_TTL', 300),
    ],

];
