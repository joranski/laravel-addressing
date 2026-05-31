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

    /*
    |--------------------------------------------------------------------------
    | Default Map Center
    |--------------------------------------------------------------------------
    |
    | Used by MapLocationField when no lat/lng is present in form state.
    | Host apps may override after publishing this config file.
    */
    'default_map_center' => [
        'lat' => 40.7128,
        'lng' => -74.0060,
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Flag Display (Filament Select labels)
    |--------------------------------------------------------------------------
    |
    | Unicode emoji flags (display: emoji) use regional-indicator characters.
    | Windows often renders those as two letters (e.g. "US") instead of a colored
    | flag because Segoe UI Emoji lacks flag glyphs. SVG mode (default) loads small
    | flag images from a CDN and works on all platforms.
    |
    | Supported: svg (default), emoji, none
    */
    'country_flags' => [
        'display' => env('ADDRESSING_COUNTRY_FLAG_DISPLAY', 'svg'),
        'svg_cdn_url' => 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/flags/4x3/%s.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | mode:
    |   auto     — use Laravel policies when registered; otherwise fallback rules
    |   policy   — always require policy checks (deny when no policy)
    |   fallback — ignore policies; use fallback rules only
    |
    | With Filament Shield, publish AddressPolicyShield and keep mode "auto".
    | Without Shield, publish the standalone policy stub or rely on fallback.
    |
    | Use AuthorizesAddressRecords on Filament address relation managers so
    | create / view / update / delete actions respect these rules.
    */
    'authorization' => [
        'mode' => 'auto',
        'fallback' => [
            'view_any' => true,
            'view' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
            'delete_any' => false,
            'restore' => false,
            'force_delete' => false,
            'force_delete_any' => false,
            'restore_any' => false,
            'replicate' => false,
            'reorder' => false,
        ],
    ],

];
