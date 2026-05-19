<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Verifier
    |--------------------------------------------------------------------------
    |
    | Selects which AddressVerifier implementation Joranski\Addressing\Contracts\AddressVerifier
    | resolves to. Supported: 'google' (default), 'null' (no-op).
    */
    'verifier' => env('ADDRESSING_VERIFIER', 'google'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | The CachedVerifier decorator wraps any verifier with a Laravel cache layer.
    | TTLs are per outcome: deliverable results are cached for a year, undeliverable
    | for 30 days, and errors are NEVER cached (set to 0) so transient API failures
    | don't poison the cache.
    */
    'cache' => [
        'store' => env('ADDRESSING_CACHE_STORE', 'database'),
        'ttl' => [
            'verified_deliverable' => 31_536_000,   // 1 year
            'verified_undeliverable' => 2_592_000,  // 30 days
            'error' => 0,                            // do NOT cache errors
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Address Validation API
    |--------------------------------------------------------------------------
    */
    'google' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'enable_usps_cass_for' => ['US', 'PR'],
        'endpoint' => 'https://addressvalidation.googleapis.com/v1:validateAddress',
    ],

];
